<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ImportBatch;
use App\Support\PhoneNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a mapped spreadsheet into Customer rows.
 *
 * Rejections are explicit and reported back to the operator rather than
 * silently dropped -- a row we cannot dial is a row someone needs to fix.
 */
class CustomerImporter
{
    /** Our internal fields, in the order the sample template presents them. */
    public const FIELDS = [
        'customer_identifier', 'name', 'phone_number', 'policy_number',
        'registered_mobile', 'policy_expiry_date', 'renewal_premium',
        'preferred_language', 'notes',
    ];

    public const REQUIRED = ['phone_number'];

    private const MAX_REJECTION_SAMPLES = 50;

    public function __construct(private readonly SpreadsheetReader $reader)
    {
    }

    /**
     * Suggest which sheet column feeds each of our fields, using the alias
     * table in config/sarvam.php. The operator can override every suggestion.
     *
     * @param  list<string>  $headers
     * @return array<string,string|null>  our field => sheet header
     */
    public function suggestMapping(array $headers): array
    {
        $aliases = (array) config('sarvam.import_aliases', []);
        $map     = [];

        $normalised = [];
        foreach ($headers as $h) {
            $normalised[$this->slug($h)] = $h;
        }

        foreach (self::FIELDS as $field) {
            $candidates = array_merge([$field], $aliases[$field] ?? []);
            $match      = null;

            foreach ($candidates as $candidate) {
                $key = $this->slug($candidate);

                if (isset($normalised[$key])) {
                    $match = $normalised[$key];
                    break;
                }
            }

            $map[$field] = $match;
        }

        return $map;
    }

    /**
     * Import every row of the batch's file using its stored column map.
     *
     * @return array{total:int,valid:int,rejected:int,duplicates:int,samples:list<array>}
     */
    public function import(ImportBatch $batch): array
    {
        $map = array_filter((array) $batch->column_map);

        if (! isset($map['phone_number'])) {
            throw new \RuntimeException('A phone number column must be mapped before importing.');
        }

        $resolved = $this->reader->resolve($batch->stored_path, $batch->original_filename);

        $total = $valid = $rejected = $duplicates = 0;
        $samples = [];
        $seen    = [];          // phone numbers seen within this file
        $maxRows = (int) config('sarvam.upload.max_rows');

        $buffer = [];

        try {
            foreach ($this->reader->rows($resolved['path'], $resolved['type']) as $index => $row) {
                $total++;

                if ($total > $maxRows) {
                    $rejected++;
                    $this->addSample($samples, $index + 2, 'Row limit exceeded', []);
                    break;
                }

                $attrs  = $this->mapRow($row, $map);
                $reason = $this->validate($attrs);

                if ($reason !== null) {
                    $rejected++;
                    $this->addSample($samples, $index + 2, $reason, $attrs);
                    continue;
                }

                $phone = $attrs['phone_number'];

                if (isset($seen[$phone])) {
                    $duplicates++;
                    $this->addSample($samples, $index + 2, 'Duplicate of an earlier row in this file', $attrs);
                    continue;
                }

                $seen[$phone] = true;
                $buffer[]     = $attrs;
                $valid++;

                if (count($buffer) >= 500) {
                    $duplicates += $this->flush($buffer, $batch);
                    $buffer = [];
                }
            }

            if ($buffer) {
                $duplicates += $this->flush($buffer, $batch);
            }
        } finally {
            $this->reader->cleanup();
        }

        return [
            'total'      => $total,
            'valid'      => $valid,
            'rejected'   => $rejected,
            'duplicates' => $duplicates,
            'samples'    => $samples,
        ];
    }

    /**
     * Upsert a chunk. An existing customer with the same phone number is
     * updated rather than duplicated, so re-uploading a corrected sheet is safe.
     *
     * @return int number of rows that matched an existing customer
     */
    private function flush(array $rows, ImportBatch $batch): int
    {
        $existing = 0;

        DB::transaction(function () use ($rows, $batch, &$existing) {
            foreach ($rows as $attrs) {
                $customer = Customer::where('phone_number', $attrs['phone_number'])->first();

                if ($customer) {
                    $existing++;
                    // Only fill blanks -- never clobber data the operator has
                    // already curated with empty cells from a new sheet.
                    foreach ($attrs as $k => $v) {
                        if ($v !== null && $v !== '' && blank($customer->{$k})) {
                            $customer->{$k} = $v;
                        }
                    }
                    $customer->import_batch_id = $batch->id;
                    $customer->save();

                    continue;
                }

                Customer::create($attrs + [
                    'customer_status'  => 'pending',
                    'import_batch_id'  => $batch->id,
                ]);
            }
        });

        return $existing;
    }

    /**
     * @param  array<string,string>  $row     sheet header => value
     * @param  array<string,string>  $map     our field => sheet header
     * @return array<string,mixed>
     */
    private function mapRow(array $row, array $map): array
    {
        $out = [];

        foreach (self::FIELDS as $field) {
            $header = $map[$field] ?? null;
            $value  = $header !== null ? trim((string) ($row[$header] ?? '')) : '';

            $out[$field] = $value === '' ? null : $value;
        }

        $out['phone_number']      = PhoneNumber::normalize($out['phone_number']);
        $out['registered_mobile'] = $out['registered_mobile'] ? PhoneNumber::normalize($out['registered_mobile']) : null;
        $out['policy_expiry_date'] = $this->parseDate($out['policy_expiry_date']);
        $out['renewal_premium']    = $this->parseMoney($out['renewal_premium']);
        $out['preferred_language'] = $this->parseLanguage($out['preferred_language']);

        return $out;
    }

    /** @return string|null rejection reason, or null when the row is usable */
    private function validate(array $attrs): ?string
    {
        if (blank($attrs['phone_number'])) {
            return 'Missing or malformed phone number';
        }

        return null;
    }

    private function addSample(array &$samples, int $line, string $reason, array $attrs): void
    {
        if (count($samples) >= self::MAX_REJECTION_SAMPLES) {
            return;
        }

        $samples[] = [
            'line'   => $line,
            'reason' => $reason,
            'name'   => $attrs['name'] ?? null,
            'phone'  => $attrs['phone_number'] ?? null,
        ];
    }

    private function parseDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        // Already ISO (the Excel reader hands us Y-m-d).
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // Prefer day-first, which is what Indian client sheets use.
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d', 'm/d/Y', 'd M Y', 'd-M-Y', 'j M Y'] as $format) {
            try {
                $d = Carbon::createFromFormat($format, $value);

                if ($d && $d->format($format) === $value) {
                    return $d->toDateString();
                }
            } catch (\Throwable) {
                // try the next format
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseMoney(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', $value);

        return is_numeric($clean) ? $clean : null;
    }

    /** Only accept languages the configured agent actually supports. */
    private function parseLanguage(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        foreach ((array) config('sarvam.languages', []) as $lang) {
            if (strcasecmp($lang, trim($value)) === 0) {
                return $lang;
            }
        }

        return null;
    }

    private function slug(string $value): string
    {
        return Str::of($value)->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString();
    }
}
