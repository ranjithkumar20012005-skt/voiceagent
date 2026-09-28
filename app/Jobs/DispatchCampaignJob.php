<?php

namespace App\Jobs;

use App\Models\CallAttempt;
use App\Models\Campaign;
use App\Models\Customer;
use App\Services\SarvamException;
use App\Services\SarvamVoiceService;
use App\Support\CallStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Creates the campaign inside the voice platform (when it does not already
 * exist) and streams its contacts up in <=1000-contact cohorts.
 */
class DispatchCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;
    public int $tries = 1; // re-dispatching would re-queue the same people

    /** @param list<int> $customerIds */
    public function __construct(
        public int $campaignId,
        public array $customerIds,
    ) {
    }

    public function handle(SarvamVoiceService $sarvam): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (! $campaign) {
            return;
        }

        $campaign->update(['status' => 'dispatching', 'error_message' => null]);

        try {
            if (! $campaign->sarvam_campaign_id) {
                $this->createRemoteCampaign($campaign, $sarvam);
            }

            $this->streamCohorts($campaign, $sarvam);

            $campaign->update(['status' => 'active']);
        } catch (SarvamException $e) {
            Log::error('campaign.dispatch_failed', [
                'campaign_id' => $campaign->id,
                'status'      => $e->status,
                'message'     => $e->getMessage(),
            ]);

            $campaign->update(['status' => 'failed', 'error_message' => $e->userMessage()]);
        }
    }

    private function createRemoteCampaign(Campaign $campaign, SarvamVoiceService $sarvam): void
    {
        // Campaign strategy: when a long-lived campaign is configured in the
        // environment, stream cohorts into that instead of creating a new one
        // per run.
        if ($configured = config('sarvam.campaign_id')) {
            $campaign->update([
                'sarvam_campaign_id' => $configured,
                'status'             => 'active',
                'config_snapshot'    => ['strategy' => 'configured_campaign'],
            ]);

            return;
        }

        $startsAt = $campaign->starts_at ?: now();
        $endsAt   = $campaign->ends_at ?: now()->addDays(7);

        $result = $sarvam->createCampaign(
            name: $campaign->name,
            startsAt: $startsAt,
            endsAt: $endsAt,
            attemptsPerSecond: $campaign->attempts_per_second ? (float) $campaign->attempts_per_second : null,
            description: $campaign->description,
            webhookMetadata: ['campaign_ref' => (string) $campaign->id],
        );

        $campaign->update([
            'sarvam_campaign_id' => $result['campaign_id'],
            'status'             => $result['status'],
            // Snapshot of what we sent, minus anything sensitive.
            'config_snapshot'    => [
                'attempts_per_second' => $campaign->attempts_per_second,
                'starts_at'           => $startsAt->toIso8601String(),
                'ends_at'             => $endsAt->toIso8601String(),
            ],
        ]);
    }

    private function streamCohorts(Campaign $campaign, SarvamVoiceService $sarvam): void
    {
        $chunkSize = min(
            SarvamVoiceService::COHORT_CHUNK_LIMIT,
            (int) config('sarvam.campaign.cohort_chunk_size', 1000)
        );

        $cohortIds  = (array) ($campaign->cohort_ids ?? []);
        $dispatched = (int) $campaign->dispatched_contacts;
        $chunkIndex = 0;

        foreach (array_chunk($this->customerIds, $chunkSize) as $chunk) {
            $chunkIndex++;

            $customers = Customer::whereIn('id', $chunk)->callable()->get();

            if ($customers->isEmpty()) {
                continue;
            }

            $users = $customers->map(fn (Customer $c) => array_filter([
                'user_phone_number' => $c->phone_number,
                'user_identifier'   => $c->customer_identifier ?: ('cust-' . $c->id),
                'app_variables'     => $c->agentVariables() ?: null,
                'app_overrides'     => $c->preferred_language
                    ? ['initial_language_name' => $c->preferred_language]
                    : null,
            ], fn ($v) => $v !== null))->values()->all();

            $result = $sarvam->streamCohort(
                $campaign->sarvam_campaign_id,
                sprintf('%s part %d', $campaign->name, $chunkIndex),
                $users,
            );

            if ($result['cohort_id']) {
                $cohortIds[] = $result['cohort_id'];
            }

            $this->recordQueuedAttempts($campaign, $customers, $result['cohort_id'] ?? null);

            $dispatched += count($users);

            $campaign->update([
                'cohort_ids'          => array_values(array_unique($cohortIds)),
                'dispatched_contacts' => $dispatched,
            ]);
        }
    }

    /**
     * Create local placeholder rows so the dashboard shows work in flight
     * before any webhook arrives. Sarvam assigns the real attempt_id per call,
     * which arrives with the webhook; these rows carry the customer link.
     */
    private function recordQueuedAttempts(Campaign $campaign, $customers, ?string $cohortId): void
    {
        foreach ($customers as $customer) {
            CallAttempt::create([
                'customer_id'             => $customer->id,
                'campaign_id'             => $campaign->sarvam_campaign_id,
                'cohort_id'               => $cohortId,
                'direction'               => 'outbound',
                'status'                  => CallStatus::DISPATCHED,
                'customer_phone_number'   => $customer->phone_number,
                'agent_phone_number'      => config('sarvam.agent_phone_number'),
                'initial_agent_variables' => $customer->agentVariables(),
            ]);

            $customer->update(['customer_status' => 'queued']);
        }
    }

    public function failed(\Throwable $e): void
    {
        Campaign::where('id', $this->campaignId)->update([
            'status'        => 'failed',
            'error_message' => 'Dispatch failed. ' . $e->getMessage(),
        ]);
    }
}
