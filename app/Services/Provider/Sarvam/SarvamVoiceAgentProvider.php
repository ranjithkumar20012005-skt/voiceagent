<?php

namespace App\Services\Provider\Sarvam;

use App\Contracts\VoiceAgentProviderInterface;
use App\Models\Agent;
use App\Services\SarvamException;
use App\Services\SarvamVoiceService;

/**
 * Resolves which hosted agent on the voice platform backs one of our agent
 * records.
 *
 * Our team builds and configures each client's agent directly in our single
 * company account on the platform, then records its identifiers against that
 * client's workspace from the internal admin area. So nothing here authors
 * anything remotely -- and nothing could: the platform publishes no
 * agent-authoring API. Its documented surface is deployments, instant outbound,
 * campaigns, tests and analytics, and the agent builder is a dashboard activity
 * with a manual Draft -> Committed -> Deployed flow.
 *
 * `createAgent` therefore means "confirm this record is mapped to a real
 * published agent". Two sources are accepted, in order:
 *
 *   1. identifiers recorded directly on the agent by an administrator -- the
 *      normal path, one hosted agent per client;
 *   2. identifiers inherited from an agent_templates row, which remains for
 *      shared master agents and is not part of the client-facing product.
 *
 * Neither path invents a provider call. The one-time manual action is documented
 * in README under "Provider prerequisites".
 */
class SarvamVoiceAgentProvider implements VoiceAgentProviderInterface
{
    public function __construct(private readonly SarvamVoiceService $voice)
    {
    }

    /**
     * @return array{provider_agent_id:string,provider_agent_version:int,provider_metadata:array}
     *
     * @throws SarvamException when the record is not mapped to a published agent
     */
    public function createAgent(Agent $agent): array
    {
        // 1. Mapped directly by an administrator.
        if (filled($agent->provider_agent_id) && $agent->provider_agent_version !== null) {
            return [
                'provider_agent_id'      => (string) $agent->provider_agent_id,
                'provider_agent_version' => (int) $agent->provider_agent_version,
                'provider_metadata'      => array_merge($agent->provider_metadata ?? [], [
                    'binding_mode' => 'direct_admin_mapping',
                    'bound_at'     => now()->toIso8601String(),
                ]),
            ];
        }

        // 2. Inherited from a shared master agent, where one is in use.
        $template = $agent->template;

        if ($template && $template->status === 'active' && $template->isProvisioned()) {
            return [
                'provider_agent_id'      => (string) $template->provider_agent_id,
                'provider_agent_version' => (int) $template->provider_agent_version,
                'provider_metadata'      => array_merge($agent->provider_metadata ?? [], [
                    'binding_mode'   => 'master_template',
                    'bound_template' => $template->slug,
                    'bound_at'       => now()->toIso8601String(),
                ]),
            ];
        }

        // Failing loudly is deliberate. The alternative is an agent that looks
        // ready in the client's dashboard and silently cannot place a call.
        throw new SarvamException(
            "Agent [{$agent->agent_ref}] is not mapped to a hosted agent yet. "
            . 'An administrator must record the hosted agent id and version for this client.'
        );
    }

    /**
     * Re-resolve after a mapping change.
     *
     * Nothing is pushed to the platform: the agent's wording and behaviour live
     * in the hosted agent our team configured, not in our database.
     */
    public function updateAgent(Agent $agent): array
    {
        return $this->createAgent($agent);
    }

    /**
     * Publishing is part of the platform's manual authoring flow, so there is
     * nothing to call. True once the mapping is usable.
     */
    public function publishAgent(Agent $agent): bool
    {
        return $agent->isProvisioned();
    }

    /**
     * Internal status for the admin area. Never rendered to a client.
     *
     * @return array<string,mixed>
     */
    public function getAgent(Agent $agent): array
    {
        return [
            'mapped'       => $agent->isProvisioned(),
            'binding_mode' => $agent->provider_metadata['binding_mode'] ?? null,
            'deployment'   => $agent->provider_deployment_id,
            'configured'   => $this->isConfigured(),
            'last_synced'  => $agent->last_synced_at?->toIso8601String(),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->voice->isConfigured();
    }
}
