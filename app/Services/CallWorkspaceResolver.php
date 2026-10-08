<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Workspace;

/**
 * Works out which client a provider callback belongs to.
 *
 * Each client has their own hosted agent in our single company account, so the
 * agent identifier in the payload is what identifies the client. That is the
 * whole mapping: payload app_id -> agents.provider_agent_id -> workspace.
 *
 * This replaces the previous check, which compared the payload's app_id against
 * the one agent configured in the environment and rejected anything else. With
 * one hosted agent per client that check would reject every client's calls.
 *
 * The environment agent is still honoured as a fallback so the original
 * single-agent setup keeps working during the transition.
 */
class CallWorkspaceResolver
{
    /**
     * @return array{workspace:Workspace,agent:?Agent}|null  null when the agent is not one of ours
     */
    public function resolve(?string $providerAgentId): ?array
    {
        $providerAgentId = is_string($providerAgentId) ? trim($providerAgentId) : '';

        if ($providerAgentId !== '') {
            // Deliberately unscoped: a webhook arrives with no session, so there
            // is no tenant in context yet -- finding the owner IS the job here.
            $agent = Agent::withoutGlobalScope('workspace')
                ->where('provider_agent_id', $providerAgentId)
                ->orderByDesc('is_default')
                ->first();

            if ($agent && $agent->workspace_id) {
                $workspace = Workspace::find($agent->workspace_id);

                if ($workspace) {
                    return ['workspace' => $workspace, 'agent' => $agent];
                }
            }

            // Not one of ours, and not the environment agent either.
            if (! $this->matchesEnvironmentAgent($providerAgentId)) {
                return null;
            }
        }

        // No app_id in the payload, or it is the environment agent: fall back to
        // the original single-workspace behaviour.
        $workspace = $this->fallbackWorkspace();

        return $workspace ? ['workspace' => $workspace, 'agent' => null] : null;
    }

    private function matchesEnvironmentAgent(string $providerAgentId): bool
    {
        $configured = (string) config('sarvam.app_id');

        return $configured !== '' && hash_equals($configured, $providerAgentId);
    }

    /**
     * Where calls land when no agent mapping identifies them: the workspace the
     * data already belonged to before multi-tenancy existed.
     */
    private function fallbackWorkspace(): ?Workspace
    {
        return Workspace::where('slug', 'default')->first()
            ?? Workspace::orderBy('id')->first();
    }
}
