<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A client's ask for the team to build an agent for them.
 *
 * The alternative to the self-serve builder: the client describes what they
 * want here instead of building it themselves, and staff pick it up from the
 * internal area's request queue. Fulfilling one is a manual, two-step action
 * -- create the agent, then link it back here -- there is no automation that
 * turns a request into an agent on its own.
 */
class AgentBuildRequest extends Model
{
    use BelongsToWorkspace;

    public const PENDING     = 'pending';
    public const IN_PROGRESS = 'in_progress';
    public const FULFILLED   = 'fulfilled';
    public const DECLINED    = 'declined';

    public const STATUSES = [
        self::PENDING     => 'Pending',
        self::IN_PROGRESS => 'In progress',
        self::FULFILLED   => 'Fulfilled',
        self::DECLINED    => 'Declined',
    ];

    protected $fillable = [
        'workspace_id', 'requested_by', 'title', 'details',
        'status', 'fulfilled_agent_id', 'admin_note',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function fulfilledAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'fulfilled_agent_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::PENDING, self::IN_PROGRESS], true);
    }

    /** What the client and staff are shown in place of the raw status value. */
    public function displayStatus(): string
    {
        return self::STATUSES[$this->status] ?? 'Pending';
    }
}
