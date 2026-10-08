<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person inside one campaign.
 *
 * The unique (campaign_id, customer_id) index is what stops a contact being
 * dialled twice when a dispatch is retried: the provider's outbound endpoint
 * has no idempotency key, so de-duplication has to happen on our side.
 */
class CampaignContact extends Model
{
    use BelongsToWorkspace;

    public const PENDING    = 'pending';
    public const QUEUED     = 'queued';
    public const DISPATCHED = 'dispatched';
    public const COMPLETED  = 'completed';
    public const FAILED     = 'failed';
    public const SKIPPED    = 'skipped';

    protected $fillable = [
        'workspace_id', 'campaign_id', 'customer_id', 'status',
        'attempts', 'last_attempt_id', 'next_attempt_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'attempts'        => 'integer',
            'next_attempt_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeDispatchable(Builder $q): Builder
    {
        return $q->whereIn('status', [self::PENDING, self::QUEUED])
            ->where(function (Builder $w) {
                $w->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
            });
    }
}
