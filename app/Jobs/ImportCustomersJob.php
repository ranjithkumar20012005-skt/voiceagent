<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\CustomerImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Parses an uploaded sheet into Customer rows off the request cycle, so a
 * 50,000-row file does not block the browser.
 */
class ImportCustomersJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;
    public int $tries = 1; // a partial re-run would double-count; fail loudly instead

    public function __construct(public int $importBatchId)
    {
    }

    public function handle(CustomerImporter $importer): void
    {
        $batch = ImportBatch::find($this->importBatchId);

        if (! $batch) {
            return;
        }

        $batch->update(['status' => ImportBatch::PROCESSING]);

        try {
            $result = $importer->import($batch);

            $batch->update([
                'status'            => ImportBatch::COMPLETED,
                'total_rows'        => $result['total'],
                'valid_rows'        => $result['valid'],
                'rejected_rows'     => $result['rejected'],
                'duplicate_rows'    => $result['duplicates'],
                'rejection_samples' => $result['samples'],
                'completed_at'      => now(),
                'error_message'     => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('import.failed', [
                'import_batch_id' => $batch->id,
                'message'         => $e->getMessage(),
            ]);

            $batch->update([
                'status'        => ImportBatch::FAILED,
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
        } finally {
            // The raw upload is not needed once it has been parsed.
            if ($batch->stored_path && Storage::disk('local')->exists($this->relative($batch->stored_path))) {
                Storage::disk('local')->delete($this->relative($batch->stored_path));
            }
        }
    }

    private function relative(string $absolute): string
    {
        $root = Storage::disk('local')->path('');

        return str_starts_with($absolute, $root)
            ? str_replace('\\', '/', substr($absolute, strlen($root)))
            : $absolute;
    }

    public function failed(\Throwable $e): void
    {
        ImportBatch::where('id', $this->importBatchId)->update([
            'status'        => ImportBatch::FAILED,
            'error_message' => $e->getMessage(),
            'completed_at'  => now(),
        ]);
    }
}
