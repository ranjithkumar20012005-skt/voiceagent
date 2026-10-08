<?php

namespace App\Contracts;

use App\Models\Agent;

/**
 * Provisioning an agent on whichever platform runs conversations.
 *
 * `createAgent` does not necessarily author anything provider-side: with a
 * hosted engine it binds our agent to a master template we maintain and returns
 * the identifiers to record. Implementations must never invent a provider call
 * that the provider does not document -- see SarvamVoiceAgentProvider.
 */
interface VoiceAgentProviderInterface
{
    /** @return array{provider_agent_id:string,provider_agent_version:int,provider_metadata:array} */
    public function createAgent(Agent $agent): array;

    /** @return array{provider_agent_id:string,provider_agent_version:int,provider_metadata:array} */
    public function updateAgent(Agent $agent): array;

    public function publishAgent(Agent $agent): bool;

    /** @return array<string,mixed> */
    public function getAgent(Agent $agent): array;

    /** True when the provider is configured well enough to provision at all. */
    public function isConfigured(): bool;
}
