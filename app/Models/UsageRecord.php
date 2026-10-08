<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one call cost us and what we charge for it.
 *
 * provider_cost is our wholesale figure and is hidden from serialization so it
 * cannot reach a customer-facing response by accident.
 */
class UsageRecord extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id', 'user_id', 'agent_id', 'call_id', 'campaign_id',
        'duration_seconds', 'billable_minutes', 'provider',
        'provider_cost', 'customer_cost', 'currency',
    ];

    protected $hidden = ['provider', 'provider_cost'];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'billable_minutes' => 'decimal:2',
            'provider_cost'    => 'decimal:4',
            'customer_cost'    => 'decimal:4',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(CallAttempt::class, 'call_id');
    }
}
