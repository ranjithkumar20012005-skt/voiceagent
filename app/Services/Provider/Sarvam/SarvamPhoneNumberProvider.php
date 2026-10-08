<?php

namespace App\Services\Provider\Sarvam;

use App\Contracts\PhoneNumberProviderInterface;
use App\Models\Agent;
use App\Models\PhoneNumber;

/**
 * Numbers on the voice platform.
 *
 * Two limitations shape this class, and neither is worked around by guessing:
 *
 * 1. There is no documented endpoint for listing the numbers a workspace owns.
 *    Renting or importing a number is a dashboard action. So inventory listing
 *    is unsupported, `listNumbers()` reports what can actually be observed --
 *    the numbers already attached to deployments -- and our own phone_numbers
 *    table remains the source of truth for the pool.
 *
 * 2. Assignment is expressed through a deployment: a deployment carries
 *    `connection_configs` with its phone numbers, and `GET /deployments` returns
 *    `phone_numbers` per deployment. That is the documented seam, so that is
 *    what is used.
 */
class SarvamPhoneNumberProvider implements PhoneNumberProviderInterface
{
    public function __construct(private readonly SarvamApiClient $client)
    {
    }

    private function deploymentsPath(?string $id = null): string
    {
        $base = "/api/app-authoring/v1/orgs/{$this->client->orgId()}/workspaces/{$this->client->workspaceId()}/deployments";

        return $id ? "{$base}/{$id}" : $base;
    }

    public function supportsInventoryListing(): bool
    {
        // No endpoint enumerates workspace inventory; see the class docblock.
        return false;
    }

    /**
     * The numbers the platform can confirm are in use, read from deployments.
     *
     * This is a reconciliation aid, not an inventory: a rented but unattached
     * number does not appear here, which is exactly why the local pool exists.
     *
     * @return array<int,array{phone_number:string,provider_number_id:?string}>
     */
    public function listNumbers(): array
    {
        if (! $this->client->isConfigured()) {
            return [];
        }

        $response = $this->client->get($this->deploymentsPath(), 'list deployments');
        $numbers  = [];

        foreach ($response['items'] ?? [] as $deployment) {
            foreach ($deployment['phone_numbers'] ?? [] as $number) {
                $numbers[] = [
                    'phone_number'       => (string) $number,
                    'provider_number_id' => isset($deployment['id']) ? (string) $deployment['id'] : null,
                ];
            }
        }

        return $numbers;
    }

    /**
     * Point a deployment at this number for the given agent.
     *
     * Returns false rather than throwing when the platform is not configured:
     * the local assignment is still valid and recorded, and the provider-side
     * mapping is reconciled later. The caller reports this as a pending sync.
     */
    public function assignNumber(PhoneNumber $number, Agent $agent): bool
    {
        if (! $this->client->isConfigured() || ! $agent->isProvisioned()) {
            return false;
        }

        $payload = [
            'name'       => "ws{$agent->workspace_id}-{$agent->agent_ref}",
            'app_id'     => $agent->provider_agent_id,
            'app_version' => $agent->provider_agent_version,
            'connection_configs' => [
                ['phone_numbers' => [$number->phone_number]],
            ],
        ];

        $response = $agent->provider_deployment_id
            ? $this->client->patch($this->deploymentsPath($agent->provider_deployment_id), $payload, 'update deployment')
            : $this->client->post($this->deploymentsPath(), $payload, 'create deployment');

        if ($id = ($response['id'] ?? $response['deployment_id'] ?? null)) {
            $agent->forceFill(['provider_deployment_id' => (string) $id])->save();
        }

        return true;
    }

    public function releaseNumber(PhoneNumber $number): bool
    {
        // Detaching is an update to the owning deployment's connection_configs.
        // With no agent recorded against the number there is nothing remote to
        // change, and the local release stands on its own.
        $agent = $number->agent;

        if (! $agent || ! $agent->provider_deployment_id || ! $this->client->isConfigured()) {
            return false;
        }

        $this->client->patch(
            $this->deploymentsPath($agent->provider_deployment_id),
            ['connection_configs' => [['phone_numbers' => []]]],
            'detach number from deployment',
        );

        return true;
    }

    /** @return array<string,mixed> */
    public function getNumberStatus(PhoneNumber $number): array
    {
        $attached = collect($this->listNumbers())->contains(
            fn (array $row) => $row['phone_number'] === $number->phone_number,
        );

        return [
            'attached_to_provider' => $attached,
            'local_status'         => $number->status,
        ];
    }
}
