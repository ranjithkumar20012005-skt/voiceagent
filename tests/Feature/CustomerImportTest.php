<?php

namespace Tests\Feature;

use App\Jobs\ImportCustomersJob;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Services\CustomerImporter;
use App\Services\SpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerImportTest extends TestCase
{
    use RefreshDatabase;

    private const CSV = <<<'CSV'
    Cust ID,Full Name,Mobile,Policy No,Expiry Date,Premium,Language
    CUST-001,Anita Sharma,9876543210,POL-2291,15/10/2026,14500,Hindi
    CUST-002,Rahul Verma,+91 98765 43211,POL-2292,2026-10-20,22000,English
    CUST-003,Priya Nair,08765432123,POL-2293,30-10-2026,18750,Tamil
    CUST-004,Bad Number,12345,POL-2294,2026-11-01,9000,Hindi
    CUST-005,No Phone,,POL-2295,2026-11-05,7000,Hindi
    CUST-006,Dupe Anita,9876543210,POL-2296,2026-11-10,14500,Hindi
    CSV;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function upload(string $contents, string $name = 'customers.csv'): \Illuminate\Testing\TestResponse
    {
        $this->signIn();

        return $this->post('/imports', [
            'file' => UploadedFile::fake()->createWithContent($name, $contents),
        ]);
    }

    // =================================================================
    // Upload validation
    // =================================================================

    public function test_it_requires_a_file(): void
    {
        $this->signIn();

        $this->post('/imports', [])->assertSessionHasErrors('file');
    }

    public function test_it_rejects_an_unsupported_extension(): void
    {
        $this->signIn();

        $this->post('/imports', [
            'file' => UploadedFile::fake()->createWithContent('malware.exe', 'MZ binary'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('import_batches', 0);
    }

    public function test_it_rejects_a_php_file_disguised_by_mime(): void
    {
        $this->signIn();

        $this->post('/imports', [
            'file' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
        ])->assertSessionHasErrors('file');
    }

    public function test_it_rejects_a_file_over_the_size_limit(): void
    {
        config(['sarvam.upload.max_file_kb' => 1]);

        $this->signIn();

        $this->post('/imports', [
            'file' => UploadedFile::fake()->create('big.csv', 2048),
        ])->assertSessionHasErrors('file');
    }

    public function test_it_rejects_a_file_with_no_header_row(): void
    {
        $this->upload('')->assertSessionHasErrors('file');
    }

    public function test_upload_requires_authentication(): void
    {
        $this->post('/imports', [
            'file' => UploadedFile::fake()->createWithContent('customers.csv', self::CSV),
        ])->assertRedirect('/login');
    }

    // =================================================================
    // Mapping
    // =================================================================

    public function test_it_suggests_a_column_mapping_from_aliases(): void
    {
        $this->upload(self::CSV)->assertRedirect();

        $batch = ImportBatch::latest('id')->first();

        $this->assertSame(ImportBatch::PENDING_MAPPING, $batch->status);
        $this->assertSame('Mobile', $batch->column_map['phone_number']);
        $this->assertSame('Full Name', $batch->column_map['name']);
        $this->assertSame('Cust ID', $batch->column_map['customer_identifier']);
        $this->assertSame('Policy No', $batch->column_map['policy_number']);
        $this->assertSame('Expiry Date', $batch->column_map['policy_expiry_date']);
    }

    public function test_it_refuses_to_process_without_a_phone_column(): void
    {
        $this->upload(self::CSV);
        $batch = ImportBatch::latest('id')->first();

        $this->post("/imports/{$batch->id}/process", [
            'map' => ['name' => 'Full Name'],
        ])->assertSessionHasErrors('map');

        $this->assertSame(ImportBatch::PENDING_MAPPING, $batch->fresh()->status);
    }

    public function test_it_ignores_a_mapping_to_an_unknown_column(): void
    {
        $this->upload(self::CSV);
        $batch = ImportBatch::latest('id')->first();

        $this->post("/imports/{$batch->id}/process", [
            'map' => ['phone_number' => 'Mobile', 'name' => 'Injected Column'],
        ])->assertRedirect();

        $this->assertArrayNotHasKey('name', $batch->fresh()->column_map);
    }

    // =================================================================
    // Importing
    // =================================================================

    public function test_it_imports_valid_rows_and_reports_rejections(): void
    {
        $this->upload(self::CSV);
        $batch = ImportBatch::latest('id')->first();

        $this->post("/imports/{$batch->id}/process", [
            'map' => [
                'customer_identifier' => 'Cust ID',
                'name'                => 'Full Name',
                'phone_number'        => 'Mobile',
                'policy_number'       => 'Policy No',
                'policy_expiry_date'  => 'Expiry Date',
                'renewal_premium'     => 'Premium',
                'preferred_language'  => 'Language',
            ],
        ])->assertRedirect();

        // QUEUE_CONNECTION=sync in phpunit.xml, so the job has already run.
        $batch->refresh();

        $this->assertSame(ImportBatch::COMPLETED, $batch->status);
        $this->assertSame(6, $batch->total_rows);
        $this->assertSame(3, $batch->valid_rows);        // Anita, Rahul, Priya
        $this->assertSame(2, $batch->rejected_rows);     // bad number, missing number
        $this->assertSame(1, $batch->duplicate_rows);    // second Anita

        $this->assertSame(3, Customer::count());
    }

    public function test_it_normalises_phone_numbers_to_e164(): void
    {
        $this->importCsv();

        $this->assertDatabaseHas('customers', ['phone_number' => '+919876543210']); // bare 10-digit
        $this->assertDatabaseHas('customers', ['phone_number' => '+919876543211']); // spaced +91
        $this->assertDatabaseHas('customers', ['phone_number' => '+918765432123']); // leading trunk 0
    }

    public function test_it_parses_day_first_and_iso_dates(): void
    {
        $this->importCsv();

        $this->assertSame('2026-10-15', Customer::where('name', 'Anita Sharma')->first()->policy_expiry_date->toDateString());
        $this->assertSame('2026-10-20', Customer::where('name', 'Rahul Verma')->first()->policy_expiry_date->toDateString());
        $this->assertSame('2026-10-30', Customer::where('name', 'Priya Nair')->first()->policy_expiry_date->toDateString());
    }

    public function test_it_records_a_reason_for_every_rejected_row(): void
    {
        $this->importCsv();

        $samples = ImportBatch::latest('id')->first()->rejection_samples;

        $reasons = array_column($samples, 'reason');

        $this->assertContains('Missing or malformed phone number', $reasons);
        $this->assertContains('Duplicate of an earlier row in this file', $reasons);
    }

    public function test_it_only_accepts_languages_the_agent_supports(): void
    {
        $this->importCsv("Name,Mobile,Language\nX,9876543210,Klingon\n", [
            'name' => 'Name', 'phone_number' => 'Mobile', 'preferred_language' => 'Language',
        ]);

        $this->assertNull(Customer::first()->preferred_language);
    }

    public function test_reimporting_updates_rather_than_duplicates(): void
    {
        $this->importCsv();
        $this->assertSame(3, Customer::count());

        $this->importCsv();
        $this->assertSame(3, Customer::count(), 'the same phone number must not create a second customer');
    }

    // =================================================================
    // ZIP handling
    // =================================================================

    public function test_it_extracts_a_zip_containing_one_csv(): void
    {
        $zip = $this->makeZip(['customers.csv' => self::CSV]);

        $reader   = new SpreadsheetReader();
        $resolved = $reader->resolve($zip, 'customers.zip');

        $this->assertSame('csv', $resolved['type']);
        $this->assertFileExists($resolved['path']);

        $preview = $reader->preview($resolved['path'], 'csv');
        $this->assertContains('Mobile', $preview['headers']);

        $reader->cleanup();
    }

    public function test_it_rejects_a_zip_with_two_spreadsheets(): void
    {
        $zip = $this->makeZip(['a.csv' => "Mobile\n9876543210\n", 'b.csv' => "Mobile\n9876543211\n"]);

        $this->expectExceptionMessage('exactly one spreadsheet');

        (new SpreadsheetReader())->resolve($zip, 'two.zip');
    }

    public function test_it_rejects_a_zip_containing_an_executable(): void
    {
        $zip = $this->makeZip(['customers.csv' => self::CSV, 'payload.exe' => 'MZ']);

        $this->expectExceptionMessage('disallowed entry');

        (new SpreadsheetReader())->resolve($zip, 'bad.zip');
    }

    public function test_it_rejects_a_nested_archive(): void
    {
        $zip = $this->makeZip(['inner.zip' => 'PK']);

        $this->expectExceptionMessage('disallowed entry');

        (new SpreadsheetReader())->resolve($zip, 'nested.zip');
    }

    public function test_it_rejects_a_zip_with_a_traversal_path(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'zip') . '.zip';

        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('../../escaped.csv', "Mobile\n9876543210\n");
        $zip->close();

        $this->expectExceptionMessage('unsafe path');

        try {
            (new SpreadsheetReader())->resolve($path, 'evil.zip');
        } finally {
            @unlink($path);
        }
    }

    public function test_it_rejects_a_zip_with_no_spreadsheet(): void
    {
        $zip = $this->makeZip(['readme.txt' => 'nothing here']);

        $this->expectExceptionMessage('unsupported entry');

        (new SpreadsheetReader())->resolve($zip, 'empty.zip');
    }

    // =================================================================
    // Helpers
    // =================================================================

    private function importCsv(?string $csv = null, ?array $map = null): void
    {
        $this->upload($csv ?? self::CSV);

        $batch = ImportBatch::latest('id')->first();

        $this->post("/imports/{$batch->id}/process", [
            'map' => $map ?? [
                'customer_identifier' => 'Cust ID',
                'name'                => 'Full Name',
                'phone_number'        => 'Mobile',
                'policy_number'       => 'Policy No',
                'policy_expiry_date'  => 'Expiry Date',
                'renewal_premium'     => 'Premium',
                'preferred_language'  => 'Language',
            ],
        ]);
    }

    /** @param array<string,string> $entries */
    private function makeZip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'zip') . '.zip';

        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return $path;
    }
}
