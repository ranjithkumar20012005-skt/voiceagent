<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Callback extends Model
{
    use BelongsToWorkspace;

    public const SCHEDULED = 'scheduled';
    public const DUE       = 'due';
    public const COMPLETED = 'completed';
    public const FAILED    = 'failed';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'workspace_id', 'agent_id', 'customer_id', 'call_id',
        'scheduled_at', 'timezone', 'reason', 'status', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(CallAttempt::class, 'call_id');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->whereIn('status', [self::SCHEDULED, self::DUE]);
    }

    public function scopeDueNow(Builder $q): Builder
    {
        return $q->pending()->where('scheduled_at', '<=', now());
    }
}
