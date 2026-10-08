<?php

namespace App\Contracts;

use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Customer;

/** Placing calls and running campaigns on the hosted platform. */
interface HostedCallingProviderInterface
{
    /** @return array{attempt_id:string} */
    public function createOutboundCall(Agent $agent, Customer $customer, array $variables = [], ?string $language = null): array;

    /** @return array{campaign_id:string} */
    public function createCampaign(Campaign $campaign): array;

    /** @param iterable<Customer> $customers @return array{accepted:int} */
    public function addContacts(Campaign $campaign, iterable $customers): array;

    public function startCampaign(Campaign $campaign): bool;

    public function pauseCampaign(Campaign $campaign): bool;

    /** @return array<string,mixed> */
    public function getCampaign(Campaign $campaign): array;

    public function isConfigured(): bool;
}
