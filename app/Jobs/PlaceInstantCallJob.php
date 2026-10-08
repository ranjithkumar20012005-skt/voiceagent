<?php

namespace App\Jobs;

use App\Contracts\HostedCallingProviderInterface;
use App\Models\Agent;
use App\Models\CallAttempt;
use App\Models\Customer;
use App\Services\CallingSchedule;
use App\Services\SarvamException;
use App\Support\Tenancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Calls one lead.
 *
 * Queued rather than run inside the intake request: a lead source gets its 200
 * back in milliseconds and the provider is never in the critical path of
 * somebody's webhook, which is what stops a slow platform turning into failed
 * lead deliveries and retries.
 *
 * Outside the agent's calling hours the job re-queues itself for the next
 * opening instead of dialling. A lead that arrives at midnight is still called
 * -- at nine, not at midnight.
 */
class PlaceInstantCallJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 180];

    public function __construct(
        public int $agentId,
        public int $customerId,
        public ?int $leadSourceId = null,
    ) {
    }

    public function handle(
        HostedCallingProviderInterface $calling,
        Tenancy $tenancy,
    ): void {
        $agent = Agent::withoutGlobalScope('workspace')->with('workspace')->find($this->agentId);

        if (! $agent || ! $agent->workspace) {
            return;
        }

        $tenancy->actingAs($agent->workspace, function () use ($agent, $calling) {
            $customer = Customer::withoutGlobalScope('workspace')->find($this->customerId);

            if (! $customer) {
                return;
            }

            // Respecting a do-not-call flag matters more than the lead.
            if ($customer->do_not_call) {
                Log::info('lead.call_skipped', [
                    'workspace_id' => $agent->workspace_id,
                    'agent_id'     => $agent->id,
                    'action'       => 'place_instant_call',
                    'reason'       => 'do_not_call',
                ]);

                return;
            }

            if (! $agent->canPlaceCalls()) {
                Log::info('lead.call_skipped', [
                    'workspace_id' => $agent->workspace_id,
                    'agent_id'     => $agent->id,
                    'action'       => 'place_instant_call',
                    'reason'       => 'agent_not_live',
                ]);

                return;
            }

            $schedule = CallingSchedule::for($agent);

            if (! $schedule->isOpen()) {
                // Held, not dropped: released at the next opening.
                self::dispatch($this->agentId, $this->customerId, $this->leadSourceId)
                    ->delay(now()->addSeconds(max(60, $schedule->delaySeconds())));

                return;
            }

            try {
                $result = $calling->createOutboundCall($agent, $customer, $this->variables($agent, $customer));
            } catch (SarvamException $e) {
                Log::warning('lead.call_failed', [
                    'workspace_id' => $agent->workspace_id,
                    'agent_id'     => $agent->id,
                    'action'       => 'place_instant_call',
                    'message'      => $e->getMessage(),
                ]);

                throw $e; // Let the queue retry with backoff.
            }

            $attempt = new CallAttempt([
                'attempt_id'            => $result['attempt_id'] ?: null,
                'customer_id'           => $customer->id,
                'agent_id'              => $agent->id,
                'direction'             => 'outbound',
                'status'                => 'dispatched',
                'customer_phone_number' => $customer->phone_number,
                'language'              => $agent->default_language,
            ]);

            $attempt->workspace_id = $agent->workspace_id;
            $attempt->save();

            Log::info('lead.called', [
                'workspace_id' => $agent->workspace_id,
                'agent_id'     => $agent->id,
                'call_id'      => $attempt->id,
                'action'       => 'place_instant_call',
            ]);
        });
    }

    /**
     * The values handed to the agent for this call.
     *
     * @return array<string,string>
     */
    private function variables(Agent $agent, Customer $customer): array
    {
        $variables = [
            'user_name'     => (string) ($customer->name ?: 'there'),
            'business_name' => (string) ($agent->workspace?->business_name ?: $agent->workspace?->name ?: ''),
        ];

        // Anything the agent declares and the customer happens to carry.
        foreach (array_keys((array) ($agent->business_variables ?? [])) as $key) {
            $value = $customer->{$key} ?? ($customer->custom_fields[$key] ?? null);

            if (is_scalar($value) && trim((string) $value) !== '') {
                $variables[$key] = (string) $value;
            }
        }

        return $variables;
    }
}
