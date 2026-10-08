<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A calling number in our pool.
 *
 * Deliberately NOT using BelongsToWorkspace: an unassigned number has no
 * workspace yet, and a global scope would hide exactly the rows the assignment
 * screen needs. Ownership is therefore filtered explicitly -- `inPool()` for
 * what a customer may claim, `ownedBy()` for what they already hold.
 */
class PhoneNumber extends Model
{
    public const AVAILABLE = 'available';
    public const ASSIGNED  = 'assigned';
    public const ACTIVE    = 'active';
    public const INACTIVE  = 'inactive';

    protected $fillable = [
        'workspace_id', 'provider', 'provider_number_id', 'phone_number',
        'country', 'capabilities', 'status', 'assigned_agent_id',
        'assigned_at', 'provider_metadata',
    ];

    /** Provider plumbing never reaches a customer-facing payload. */
    protected $hidden = ['provider', 'provider_number_id', 'provider_metadata'];

    protected function casts(): array
    {
        return [
            'capabilities'      => 'array',
            'provider_metadata' => 'array',
            'assigned_at'       => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'assigned_agent_id');
    }

    /** Claimable by anyone: in the pool and not held by a workspace. */
    public function scopeInPool(Builder $q): Builder
    {
        return $q->whereNull('workspace_id')->where('status', self::AVAILABLE);
    }

    public function scopeOwnedBy(Builder $q, Workspace|int $workspace): Builder
    {
        return $q->where('workspace_id', $workspace instanceof Workspace ? $workspace->id : $workspace);
    }

    public function isHeld(): bool
    {
        return $this->workspace_id !== null;
    }

    /** What the customer sees in place of the raw status. */
    public function displayStatus(): string
    {
        return match ($this->status) {
            self::ASSIGNED => 'Assigned',
            self::ACTIVE   => 'Active',
            self::INACTIVE => 'Inactive',
            default        => 'Available',
        };
    }
}
