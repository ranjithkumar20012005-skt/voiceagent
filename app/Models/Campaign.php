<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    use BelongsToWorkspace;
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name', 'description', 'sarvam_campaign_id', 'cohort_ids', 'status',
        'import_batch_id', 'automation_id', 'user_id', 'total_contacts',
        'dispatched_contacts', 'attempts_per_second', 'config_snapshot',
        'error_message', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'cohort_ids'          => 'array',
            'config_snapshot'     => 'array',
            'starts_at'           => 'datetime',
            'ends_at'             => 'datetime',
            'total_contacts'      => 'integer',
            'dispatched_contacts' => 'integer',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function attempts()
    {
        return $this->hasMany(CallAttempt::class, 'campaign_id', 'sarvam_campaign_id');
    }

    /** Only campaigns Sarvam actually knows about can be paused/resumed. */
    public function isRemote(): bool
    {
        return ! empty($this->sarvam_campaign_id);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'      => 'badge-green',
            'scheduled'   => 'badge-cyan',
            'paused'      => 'badge-orange',
            'dispatching' => 'badge-cyan',
            'ended'       => 'badge-grey',
            'cancelled', 'failed' => 'badge-red',
            default       => 'badge-grey',
        };
    }
}
