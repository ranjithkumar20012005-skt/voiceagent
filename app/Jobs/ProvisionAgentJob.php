<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Services\Provider\AgentProvisioner;
use App\Support\Tenancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Provisions a newly created agent on the voice platform.
 *
 * Queued so submitting the form returns straight away rather than waiting on the
 * platform, and retried with backoff because a transient refusal should not leave
 * a client's agent stuck. The provisioner itself is idempotent -- an agent that
 * already carries provider identifiers is synced rather than created again.
 */
class ProvisionAgentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Seconds between attempts: quick, then give the platform room. */
    public array $backoff = [10, 60];

    public function __construct(public int $agentId)
    {
    }

    public function handle(AgentProvisioner $provisioner, Tenancy $tenancy): void
    {
        // Unscoped: a queued job has no session, so the tenant comes from the row.
        $agent = Agent::withoutGlobalScope('workspace')->with('workspace')->find($this->agentId);

        if (! $agent || ! $agent->workspace) {
            return;
        }

        $tenancy->actingAs($agent->workspace, fn () => $provisioner->provision($agent));
    }
}
