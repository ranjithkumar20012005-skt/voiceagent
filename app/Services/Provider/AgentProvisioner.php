<?php

namespace App\Services\Provider;

use App\Models\Agent;
use App\Services\Provider\Sarvam\SarvamApiClient;
use App\Services\SarvamException;
use Illuminate\Support\Facades\Log;

/**
 * Creates the agent on the voice platform from what a customer filled in.
 *
 * It always tries the platform's authoring API first, and only falls back to
 * flagging the agent for an operator when that API is genuinely unavailable.
 * That ordering is the point: the fallback exists because the authoring endpoint
 * is not on the key-authenticated REST surface today (the platform's own
 * management client reaches it another way), and the moment it is, every agent
 * provisions in the same second it is submitted with nothing here to change.
 *
 * Statuses it sets:
 *   ready        - the agent exists on the platform and can place calls
 *   provisioning - submitted, waiting on the one-click operator step
 *   error        - the platform refused the config; the reason is recorded
 */
class AgentProvisioner
{
    /** Paths tried, in order. The first that is not a 404 wins. */
    private const AUTHORING_PATHS = [
        '/api/app-authoring/v1/orgs/{org}/workspaces/{ws}/apps',
        '/api/app-authoring/v1/orgs/{org}/workspaces/{ws}/agents',
    ];

    public function __construct(private readonly SarvamApiClient $client)
    {
    }

    /**
     * @return array{provisioned:bool,reason:?string}
     */
    public function provision(Agent $agent): array
    {
        $blueprint = AgentBlueprint::for($agent);

        $agent->forceFill([
            'status'                 => Agent::PROVISIONING,
            'provision_requested_at' => now(),
            'error_message'          => null,
        ])->save();

        if (! $this->client->isConfigured()) {
            return $this->defer($agent, 'The voice platform is not configured on this server.');
        }

        $payload = [
            'name'   => $this->platformName($agent),
            'prompt' => $blueprint->prompt(),
            'config' => $blueprint->config() + ['channel_type' => 'v2v'],
        ];

        foreach (self::AUTHORING_PATHS as $template) {
            $path = str_replace(
                ['{org}', '{ws}'],
                [$this->client->orgId(), $this->client->workspaceId()],
                $template,
            );

            try {
                $response = $this->client->post($path, $payload, 'create agent');
            } catch (SarvamException $e) {
                // A 404 means this path is not the authoring endpoint; try the
                // next. Anything else is a real refusal and is reported as one.
                if (str_contains($e->getMessage(), '(404)')) {
                    continue;
                }

                return $this->failed($agent, $e->getMessage());
            }

            $id      = $response['app_id'] ?? $response['id'] ?? null;
            $version = $response['app_version'] ?? $response['version'] ?? 1;

            if (! $id) {
                return $this->failed($agent, 'The platform accepted the agent but returned no identifier.');
            }

            $agent->forceFill([
                'provider'               => config('voice.provider', 'sarvam'),
                'provider_agent_id'      => (string) $id,
                'provider_agent_version' => (int) $version,
                'provider_metadata'      => array_merge($agent->provider_metadata ?? [], [
                    'binding_mode'   => 'authored_from_dashboard',
                    'authored_at'    => now()->toIso8601String(),
                    'authoring_path' => $path,
                ]),
                'status'                 => Agent::READY,
                'error_message'          => null,
                'last_synced_at'         => now(),
            ])->save();

            Log::info('voice.agent.authored', [
                'workspace_id' => $agent->workspace_id,
                'agent_id'     => $agent->id,
                'action'       => 'create_agent',
            ]);

            return ['provisioned' => true, 'reason' => null];
        }

        // Every candidate path 404'd: authoring is not exposed to this key.
        return $this->defer(
            $agent,
            'Automatic setup is not available on this account yet, so this agent is queued for our team to finish.',
        );
    }

    /** Push an edited agent back to the platform. */
    public function sync(Agent $agent): array
    {
        if (! $agent->isProvisioned()) {
            return $this->provision($agent);
        }

        if (! $this->client->isConfigured()) {
            return ['provisioned' => false, 'reason' => 'The voice platform is not configured on this server.'];
        }

        $blueprint = AgentBlueprint::for($agent);

        foreach (self::AUTHORING_PATHS as $template) {
            $path = str_replace(
                ['{org}', '{ws}'],
                [$this->client->orgId(), $this->client->workspaceId()],
                $template,
            ) . '/' . $agent->provider_agent_id;

            try {
                $this->client->patch($path, [
                    'prompt' => $blueprint->prompt(),
                    'config' => $blueprint->config(),
                ], 'update agent');
            } catch (SarvamException $e) {
                if (str_contains($e->getMessage(), '(404)')) {
                    continue;
                }

                return $this->failed($agent, $e->getMessage());
            }

            $agent->forceFill(['last_synced_at' => now(), 'error_message' => null])->save();

            return ['provisioned' => true, 'reason' => null];
        }

        return ['provisioned' => false, 'reason' => 'Changes are saved and queued for our team to apply.'];
    }

    /**
     * A name that is unique on the platform and traceable back to the client.
     * The platform refuses a duplicate name, and two clients may well both call
     * their agent "Maya".
     */
    private function platformName(Agent $agent): string
    {
        $business = $agent->workspace?->business_name ?: $agent->workspace?->name ?: 'Client';

        return trim("{$business} {$agent->name} {$agent->agent_ref}");
    }

    private function defer(Agent $agent, string $reason): array
    {
        $agent->forceFill(['status' => Agent::PROVISIONING, 'error_message' => $reason])->save();

        Log::info('voice.agent.provision_deferred', [
            'workspace_id' => $agent->workspace_id,
            'agent_id'     => $agent->id,
            'action'       => 'create_agent',
            'reason'       => $reason,
        ]);

        return ['provisioned' => false, 'reason' => $reason];
    }

    private function failed(Agent $agent, string $reason): array
    {
        $agent->forceFill(['status' => Agent::ERROR, 'error_message' => $reason])->save();

        Log::warning('voice.agent.provision_failed', [
            'workspace_id' => $agent->workspace_id,
            'agent_id'     => $agent->id,
            'action'       => 'create_agent',
        ]);

        return ['provisioned' => false, 'reason' => $reason];
    }
}
