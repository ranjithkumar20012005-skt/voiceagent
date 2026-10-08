<?php

namespace App\Services\Provider\Sarvam;

use App\Contracts\TestProviderInterface;
use App\Models\Agent;
use App\Services\SarvamException;

/**
 * The platform's own checks against an agent.
 *
 * These are simulations: a synthetic caller holds a conversation with the agent
 * and each expected behaviour is graded pass or fail, with a transcript. No real
 * telephony is involved, which is why the product offers "Call me to test"
 * separately -- that one goes out through instant outbound as an ordinary call
 * and lands in the normal call history.
 *
 * A test suite is authored in the dashboard alongside the master agent, so the
 * suite id is recorded on the template rather than created from here.
 */
class SarvamTestProvider implements TestProviderInterface
{
    public function __construct(private readonly SarvamApiClient $client)
    {
    }

    private function suitePath(string $suiteId): string
    {
        return "/api/evals/v1/{$this->client->orgId()}/{$this->client->workspaceId()}/test-suites/{$suiteId}";
    }

    /**
     * @return array{run_id:string,status:string}
     *
     * @throws SarvamException
     */
    public function runChecks(Agent $agent): array
    {
        if (! $agent->isProvisioned()) {
            throw new SarvamException('This agent is not connected to the voice platform yet.');
        }

        $suiteId = $agent->template?->provider_metadata['test_suite_id'] ?? null;

        // Not every master agent has a published suite. Saying so is better than
        // reporting a pass the platform never gave us.
        if (! $suiteId) {
            throw new SarvamException('No checks have been published for this agent type yet.');
        }

        $response = $this->client->post(
            $this->suitePath((string) $suiteId) . '/runs',
            [
                'app_id'      => $agent->provider_agent_id,
                'app_version' => $agent->provider_agent_version,
                'run_name'    => "{$agent->agent_ref}-" . now()->format('YmdHis'),
                'on_end_eval' => true,
            ],
            'run checks',
        );

        return [
            'run_id' => (string) ($response['run_id'] ?? ''),
            'status' => (string) ($response['status'] ?? 'created'),
        ];
    }

    /** @return array{status:string,results:array<int,array<string,mixed>>} */
    public function getRunResult(Agent $agent, string $runId): array
    {
        $suiteId = $agent->template?->provider_metadata['test_suite_id'] ?? null;

        if (! $suiteId) {
            return ['status' => 'unavailable', 'results' => []];
        }

        $response = $this->client->get(
            $this->suitePath((string) $suiteId) . "/runs/{$runId}",
            'get check run',
        );

        return [
            'status'  => (string) ($response['status'] ?? 'unknown'),
            'results' => $response['results'] ?? $response['items'] ?? [],
        ];
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }
}
