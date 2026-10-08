<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A voice agent a customer owns.
 *
 * The agent is ours: the customer names it, writes its greeting and sets its
 * goal here, and `agent_ref` is the only identifier they ever see. It maps onto
 * a master template we maintain on the voice platform, and the customer's own
 * wording travels per call as variables and overrides rather than becoming a
 * separate agent on the provider side.
 *
 * Every provider_* attribute is hidden from serialization: none of them may
 * appear in a customer-facing response, view or API payload.
 */
class Agent extends Model
{
    use BelongsToWorkspace;
    use HasFactory;

    // Lifecycle. `active` is kept as the original value so existing rows,
    // which were created before provisioning existed, stay usable.
    public const DRAFT        = 'draft';
    public const PROVISIONING = 'provisioning';
    public const READY        = 'ready';
    public const TESTING      = 'testing';
    public const ACTIVE       = 'active';
    public const PAUSED       = 'paused';
    public const ERROR        = 'error';
    public const ARCHIVED     = 'archived';
    public const INACTIVE     = 'inactive';

    /** Statuses from which the agent may place calls. */
    public const CALLABLE_STATUSES = [self::READY, self::TESTING, self::ACTIVE];

    public const MODE_INSTANT_LEADS = 'instant_leads';
    public const MODE_BULK          = 'bulk_campaigns';
    public const MODE_INBOUND       = 'inbound';

    public const CALLING_MODES = [
        self::MODE_INSTANT_LEADS => 'Instant Leads',
        self::MODE_BULK          => 'Bulk Campaigns',
        self::MODE_INBOUND       => 'Inbound',
    ];

    protected $fillable = [
        'workspace_id', 'agent_ref', 'template_id',
        'name', 'role', 'description', 'calling_mode',
        'default_language', 'voice',
        'first_message', 'instructions', 'goal',
        'closing_message', 'objection_handling', 'faqs',
        'business_variables', 'custom_variables',
        'secondary_languages', 'max_call_seconds', 'temperature',
        'is_always_on', 'calling_window_start', 'calling_window_end', 'calling_days', 'timezone',
        'status', 'is_default', 'assigned_phone_number_id',
    ];

    /**
     * Provider plumbing and internal diagnostics. Not fillable (only the
     * provisioning service writes them) and not serialized, so they cannot
     * reach the browser through a model-to-JSON path.
     */
    protected $hidden = [
        'provider', 'provider_agent_id', 'provider_agent_version',
        'provider_deployment_id', 'provider_metadata', 'error_message',
        'platform_app_id', 'platform_app_version',
    ];

    protected function casts(): array
    {
        return [
            'is_default'             => 'boolean',
            'provider_agent_version' => 'integer',
            'platform_app_version'   => 'integer',
            'faqs'                   => 'array',
            'secondary_languages'    => 'array',
            'is_always_on'           => 'boolean',
            'calling_days'           => 'array',
            'max_call_seconds'       => 'integer',
            'temperature'            => 'float',
            'provision_requested_at' => 'datetime',
            'business_variables'     => 'array',
            'custom_variables'       => 'array',
            'provider_metadata'      => 'array',
            'last_synced_at'         => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Customers never choose their own reference, and it has to be unique
        // inside the workspace rather than globally so each customer sees
        // agent_01, agent_02 ... of their own.
        static::created(function (Agent $agent) {
            if (blank($agent->agent_ref)) {
                $n = static::withoutGlobalScope('workspace')
                    ->where('workspace_id', $agent->workspace_id)
                    ->where('id', '<=', $agent->id)
                    ->count();

                $agent->forceFill(['agent_ref' => 'agent_' . str_pad((string) $n, 2, '0', STR_PAD_LEFT)])->save();
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AgentTemplate::class, 'template_id');
    }

    public function callAttempts(): HasMany
    {
        return $this->hasMany(CallAttempt::class);
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class, 'assigned_phone_number_id');
    }

    public function leadSources(): HasMany
    {
        return $this->hasMany(AgentLeadSource::class);
    }

    public function callbacks(): HasMany
    {
        return $this->hasMany(Callback::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('status', self::CALLABLE_STATUSES);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::CALLABLE_STATUSES, true);
    }

    public function canPlaceCalls(): bool
    {
        return $this->isActive() && $this->isProvisioned();
    }

    /** True once the agent is pointing at a usable provider agent. */
    public function isProvisioned(): bool
    {
        return filled($this->provider_agent_id) && $this->provider_agent_version !== null;
    }

    /**
     * True while automatic provisioning is actively in flight -- the moment
     * between the create form and the platform answering, with nothing yet
     * decided either way. This is the "building" card on the Agents page.
     */
    public function isBuilding(): bool
    {
        return ! $this->isProvisioned() && $this->status !== self::ERROR && blank($this->error_message);
    }

    /**
     * True once automatic provisioning has given up and handed the agent to a
     * person to finish -- see AgentProvisioner::defer(). Distinct from
     * isBuilding() so the Agents page stops animating a card nothing is
     * actively working on and shows a calmer "with our team" state instead.
     */
    public function isAwaitingTeam(): bool
    {
        return $this->status === self::PROVISIONING && filled($this->error_message);
    }

    /**
     * The provider agent to run this call against, or null to fall back to the
     * agent configured in the environment.
     *
     * @return array{app_id:string,app_version:int}|null
     */
    public function providerApp(): ?array
    {
        return $this->isProvisioned()
            ? ['app_id' => (string) $this->provider_agent_id, 'app_version' => (int) $this->provider_agent_version]
            : null;
    }

    /**
     * Make this agent the workspace default.
     *
     * Scoped to the workspace: the previous implementation cleared the flag on
     * every agent in the database, which reached across tenants.
     */
    public function markAsDefault(): void
    {
        static::withoutGlobalScope('workspace')
            ->where('workspace_id', $this->workspace_id)
            ->whereKeyNot($this->id)
            ->update(['is_default' => false]);

        $this->forceFill(['is_default' => true])->save();
    }

    /** What the customer is shown in place of the raw status value. */
    public function displayStatus(): string
    {
        return match ($this->status) {
            self::DRAFT        => 'Draft',
            self::PROVISIONING => 'Setting up',
            self::READY        => 'Ready',
            self::TESTING      => 'Testing',
            self::ACTIVE       => 'Active',
            self::PAUSED       => 'Paused',
            self::ARCHIVED     => 'Archived',
            self::ERROR        => 'Needs attention',
            default            => 'Inactive',
        };
    }

    public function callingModeLabel(): string
    {
        return self::CALLING_MODES[$this->calling_mode] ?? 'Instant Leads';
    }
}
