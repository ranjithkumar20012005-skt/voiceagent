<?php

namespace App\Services\Provider\Sarvam;

use App\Contracts\HostedCallingProviderInterface;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Customer;
use App\Services\SarvamVoiceService;

/**
 * Adapter over the existing SarvamVoiceService.
 *
 * That class already speaks the platform's outbound and campaign endpoints
 * correctly and is in production use, so it is wrapped rather than rewritten.
 * The point of the wrapper is that controllers and jobs depend on
 * HostedCallingProviderInterface instead of a vendor class name.
 */
class SarvamHostedCallingProvider implements HostedCallingProviderInterface
{
    public function __construct(private readonly SarvamVoiceService $voice)
    {
    }

    /** @return array{attempt_id:string} */
    public function createOutboundCall(Agent $agent, Customer $customer, array $variables = [], ?string $language = null): array
    {
        $result = $this->voice->createInstantCall(
            $customer->phone_number,
            $variables,
            $language ?? $agent->default_language,
            // The metadata the result webhook echoes back, which is how the
            // callback is tied to this customer rather than guessed from the
            // dialled number. It is a required array, not nullable.
            ['customer_id' => $customer->id, 'agent_id' => $agent->id],
            $agent->providerApp(),
        );

        return ['attempt_id' => (string) ($result['attempt_id'] ?? '')];
    }

    /** @return array{campaign_id:string} */
    public function createCampaign(Campaign $campaign): array
    {
        $result = $this->voice->createCampaign(
            $campaign->name,
            $campaign->description,
            (float) ($campaign->attempts_per_second ?? 0),
        );

        return ['campaign_id' => (string) ($result['campaign_id'] ?? $result['id'] ?? '')];
    }

    /**
     * @param  iterable<Customer>  $customers
     * @return array{accepted:int}
     */
    public function addContacts(Campaign $campaign, iterable $customers): array
    {
        $users = [];

        foreach ($customers as $customer) {
            $users[] = [
                'user_phone_number' => $customer->phone_number,
                'user_identifier'   => (string) $customer->id,
            ];
        }

        if ($users === []) {
            return ['accepted' => 0];
        }

        $this->voice->streamCohort(
            (string) $campaign->sarvam_campaign_id,
            'cohort-' . $campaign->id,
            $users,
        );

        return ['accepted' => count($users)];
    }

    public function startCampaign(Campaign $campaign): bool
    {
        $this->voice->updateCampaignStatus((string) $campaign->sarvam_campaign_id, 'start');

        return true;
    }

    public function pauseCampaign(Campaign $campaign): bool
    {
        $this->voice->updateCampaignStatus((string) $campaign->sarvam_campaign_id, 'pause');

        return true;
    }

    /** @return array<string,mixed> */
    public function getCampaign(Campaign $campaign): array
    {
        return $this->voice->getCampaign((string) $campaign->sarvam_campaign_id);
    }

    public function isConfigured(): bool
    {
        return $this->voice->isConfigured();
    }
}
