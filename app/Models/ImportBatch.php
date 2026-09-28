<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    public const PENDING_MAPPING = 'pending_mapping';
    public const QUEUED          = 'queued';
    public const PROCESSING      = 'processing';
    public const COMPLETED       = 'completed';
    public const FAILED          = 'failed';

    protected $fillable = [
        'original_filename', 'stored_path', 'file_type', 'file_size', 'status',
        'column_map', 'detected_headers', 'total_rows', 'valid_rows',
        'rejected_rows', 'duplicate_rows', 'rejection_samples', 'error_message',
        'user_id', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'column_map'        => 'array',
            'detected_headers'  => 'array',
            'rejection_samples' => 'array',
            'completed_at'      => 'datetime',
            'total_rows'        => 'integer',
            'valid_rows'        => 'integer',
            'rejected_rows'     => 'integer',
            'duplicate_rows'    => 'integer',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::PENDING_MAPPING => 'Awaiting mapping',
            self::QUEUED          => 'Queued',
            self::PROCESSING      => 'Processing',
            self::COMPLETED       => 'Completed',
            self::FAILED          => 'Failed',
            default               => ucfirst((string) $this->status),
        };
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::COMPLETED, self::FAILED], true);
    }
}
