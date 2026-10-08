<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\CallAttempt;
use App\Models\Callback;
use App\Models\Customer;
use App\Models\UsageRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The client-facing product: conversations, results, callbacks, usage.
 *
 * Two things are proved throughout: one client can never reach another's
 * records, and the callback and usage writes survive the provider delivering the
 * same webhook more than once.
 */
class ClientProductTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-webhook-token-0123456789';

    private function url(): string
    {
        return '/api/webhooks/sarvam/' . self::TOKEN;
    }

    /** A client whose hosted agent is mapped, so callbacks resolve to them. */
    private function client(string $name, string $providerAgentId, string $email): array
    {
        [$workspace, $user] = $this->makeClient($name, $email);

        $agent = $this->withinWorkspace($workspace, function () use ($name, $providerAgentId) {
            $agent = Agent::create(['name' => $name . ' agent', 'status' => Agent::ACTIVE]);
            $agent->forceFill(['provider_agent_id' => $providerAgentId, 'provider_agent_version' => 1])->save();

            return $agent;
        });

        return [$workspace, $user, $agent];
    }

    /** @return array{0:CallAttempt,1:Customer} */
    private function completedCall(\App\Models\Workspace $workspace, Agent $agent, array $overrides = []): array
    {
        return $this->withinWorkspace($workspace, function () use ($agent, $overrides) {
            $customer = Customer::create([
                'name'         => $overrides['customer'] ?? 'Ravi Kumar',
                'phone_number' => $overrides['phone'] ?? '+919000001111',
            ]);

            $call = new CallAttempt(array_merge([
                'attempt_id'          => $overrides['attempt_id'] ?? 'att-' . uniqid(),
                'customer_id'         => $customer->id,
                'agent_id'            => $agent->id,
                'status'              => 'completed',
                'connectivity_status' => 'connected',
                'call_disposition'    => 'interested',
                'duration_seconds'    => 186,
                'language'            => 'Telugu',
                'summary'             => $overrides['summary'] ?? 'Asked about a cardiologist appointment.',
                'transcript'          => [
                    ['role' => 'agent', 'en_text' => 'Hello Ravi, calling from the clinic.'],
                    ['role' => 'user', 'en_text' => 'I want an appointment with a cardiologist.'],
                ],
            ], $overrides['call'] ?? []));

            $call->workspace_id = $agent->workspace_id;
            $call->save();

            return [$call, $customer];
        });
    }

    // =================================================================
    // Conversations
    // =================================================================

    public function test_a_conversation_shows_its_own_transcript_and_summary(): void
    {
        [$workspace, $user, $agent] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');
        [$call] = $this->completedCall($workspace, $agent);

        $this->actingAsClient($workspace, $user);

        $this->get("/conversations/{$call->id}")
            ->assertOk()
            ->assertSee('Ravi Kumar')
            ->assertSee('Hello Ravi, calling from the clinic.')
            ->assertSee('I want an appointment with a cardiologist.')
            ->assertSee('Asked about a cardiologist appointment.')
            // Speakers are named, not printed as raw roles.
            ->assertSee('ABC Hospital agent')
            ->assertDontSee('en_text');
    }

    public function test_a_call_with_no_transcript_says_so_rather_than_failing(): void
    {
        [$workspace, $user, $agent] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');

        [$call] = $this->completedCall($workspace, $agent, [
            'call' => ['transcript' => null, 'summary' => null, 'connectivity_status' => 'no_answer'],
        ]);

        $this->actingAsClient($workspace, $user);

        $this->get("/calls/{$call->id}")
            ->assertOk()
            ->assertSee('No conversation recorded')
            ->assertSee('Summary not available for this call.');
    }

    public function test_a_client_cannot_open_another_clients_conversation(): void
    {
        [$a, $userA] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');
        [$b, $_ub, $agentB] = $this->client('XYZ Realty', 'xyz-002', 'b@example.test');

        [$theirCall] = $this->completedCall($b, $agentB, [
            'customer' => 'Sneha Rao',
            'phone'    => '+919000002222',
            'summary'  => 'Asked about a 3BHK site visit.',
        ]);

        $this->actingAsClient($a, $userA);

        $this->get("/conversations/{$theirCall->id}")
            ->assertNotFound()
            ->assertDontSee('Sneha Rao')
            ->assertDontSee('3BHK');

        $this->get('/conversations')->assertOk()->assertDontSee('Sneha Rao');
    }

    // =================================================================
    // Callbacks
    // =================================================================

    public function test_a_callback_is_created_from_a_final_webhook(): void
    {
        [$workspace, $user] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');

        $customer = $this->withinWorkspace($workspace, fn () => Customer::create([
            'customer_identifier' => 'CUST-1',
            'name'                => 'Ravi Kumar',
            'phone_number'        => '+919000001111',
        ]));

        $this->postJson($this->url(), [
            'attempt_id'      => 'att-cb',
            'app_id'          => 'abc-001',
            'status'          => 'connected',
            'duration'        => 120,
            'user_identifier' => 'CUST-1',
            'output_agent_variables' => [
                'call_disposition'  => 'callback',
                'callback_required' => true,
                'callback_at'       => now()->addDay()->toIso8601String(),
                'callback_reason'   => 'Prefers Saturday afternoon',
            ],
        ])->assertOk();

        $callback = Callback::withoutGlobalScope('workspace')->firstOrFail();

        $this->assertSame($workspace->id, $callback->workspace_id);
        $this->assertSame($customer->id, $callback->customer_id);
        $this->assertSame('Prefers Saturday afternoon', $callback->reason);
        $this->assertSame(Callback::SCHEDULED, $callback->status);
        $this->assertNotNull($callback->call_id);

        $this->actingAsClient($workspace, $user);
        $this->get('/callbacks')->assertOk()->assertSee('Ravi Kumar')->assertSee('Prefers Saturday afternoon');
    }

    public function test_a_duplicate_webhook_does_not_create_a_second_callback(): void
    {
        [$workspace] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');

        // A callback needs somebody to call, so the customer has to exist.
        $this->withinWorkspace($workspace, fn () => Customer::create([
            'customer_identifier' => 'CUST-D',
            'name'                => 'Ravi Kumar',
            'phone_number'        => '+919000001111',
        ]));

        $payload = [
            'attempt_id'      => 'att-cb-dupe',
            'app_id'          => 'abc-001',
            'status'          => 'connected',
            'duration'        => 90,
            'user_identifier' => 'CUST-D',
            'output_agent_variables' => [
                'call_disposition'  => 'callback',
                'callback_required' => true,
                'callback_at'       => now()->addDay()->toIso8601String(),
            ],
        ];

        $this->postJson($this->url(), $payload)->assertOk();
        $this->postJson($this->url(), $payload)->assertOk();
        $this->postJson($this->url(), $payload)->assertOk();

        $this->assertSame(1, Callback::withoutGlobalScope('workspace')->count());
    }

    public function test_a_callback_no_longer_wanted_is_cancelled_not_deleted(): void
    {
        [$workspace] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');

        $this->withinWorkspace($workspace, fn () => Customer::create([
            'customer_identifier' => 'CUST-C',
            'name'                => 'Ravi Kumar',
            'phone_number'        => '+919000001111',
        ]));

        $base = [
            'attempt_id'      => 'att-cb-change',
            'app_id'          => 'abc-001',
            'status'          => 'connected',
            'duration'        => 60,
            'user_identifier' => 'CUST-C',
        ];

        $this->postJson($this->url(), $base + ['output_agent_variables' => [
            'callback_required' => true,
            'callback_at'       => now()->addDay()->toIso8601String(),
        ]])->assertOk();

        // A corrected result that no longer asks for one.
        $this->postJson($this->url(), $base + ['output_agent_variables' => [
            'call_disposition'  => 'not_interested',
            'callback_required' => false,
        ]])->assertOk();

        $callback = Callback::withoutGlobalScope('workspace')->firstOrFail();

        // The request still happened, so the record stays -- as cancelled.
        $this->assertSame(Callback::CANCELLED, $callback->status);
        $this->assertSame(1, Callback::withoutGlobalScope('workspace')->count());
    }

    public function test_a_client_cannot_see_another_clients_callbacks(): void
    {
        [$a, $userA] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');
        [$b]         = $this->client('XYZ Realty', 'xyz-002', 'b@example.test');

        $this->withinWorkspace($b, fn () => Customer::create([
            'name'         => 'Sneha Rao',
            'phone_number' => '+919000002222',
        ]));

        $this->postJson($this->url(), [
            'attempt_id' => 'att-xyz-cb',
            'app_id'     => 'xyz-002',
            'status'     => 'connected',
            'duration'   => 60,
            'user_phone_number' => '+919000002222',
            'output_agent_variables' => ['callback_required' => true, 'callback_at' => now()->addDay()->toIso8601String()],
        ])->assertOk();

        $theirs = Callback::withoutGlobalScope('workspace')->firstOrFail();

        $this->actingAsClient($a, $userA);

        $this->assertSame(0, $this->withinWorkspace($a, fn () => Callback::count()));
        $this->post("/callbacks/{$theirs->id}/cancel")->assertNotFound();
        $this->assertSame(Callback::SCHEDULED, $theirs->fresh()->status);
    }

    // =================================================================
    // Usage metering
    // =================================================================

    public function test_a_usage_record_is_created_once_per_call(): void
    {
        [$workspace, $user] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');

        $this->postJson($this->url(), [
            'attempt_id' => 'att-usage',
            'app_id'     => 'abc-001',
            'status'     => 'connected',
            'duration'   => 186,
        ])->assertOk();

        $usage = UsageRecord::withoutGlobalScope('workspace')->firstOrFail();

        $this->assertSame($workspace->id, $usage->workspace_id);
        $this->assertSame(186, $usage->duration_seconds);
        // 186s is four started minutes.
        $this->assertSame('4.00', (string) $usage->billable_minutes);

        $this->actingAsClient($workspace, $user);
        $this->get('/usage')->assertOk()->assertSee('Minutes this month');
    }

    public function test_a_triple_replay_produces_exactly_one_usage_record(): void
    {
        $this->client('ABC Hospital', 'abc-001', 'a@example.test');

        $payload = ['attempt_id' => 'att-usage-dupe', 'app_id' => 'abc-001', 'status' => 'connected', 'duration' => 120];

        $this->postJson($this->url(), $payload)->assertOk();
        $this->postJson($this->url(), $payload)->assertOk();
        $this->postJson($this->url(), $payload)->assertOk();

        $this->assertSame(1, UsageRecord::withoutGlobalScope('workspace')->count());
        $this->assertSame('2.00', (string) UsageRecord::withoutGlobalScope('workspace')->first()->billable_minutes);
    }

    public function test_a_call_that_never_connected_is_not_metered(): void
    {
        $this->client('ABC Hospital', 'abc-001', 'a@example.test');

        $this->postJson($this->url(), [
            'attempt_id' => 'att-noanswer',
            'app_id'     => 'abc-001',
            'status'     => 'no_answer',
            'duration'   => 0,
        ])->assertOk();

        // Nothing was spoken, so there is nothing to bill.
        $this->assertSame(0, UsageRecord::withoutGlobalScope('workspace')->count());
    }

    public function test_usage_is_not_shared_between_clients(): void
    {
        [$a, $userA] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');
        $this->client('XYZ Realty', 'xyz-002', 'b@example.test');

        $this->postJson($this->url(), ['attempt_id' => 'u-a', 'app_id' => 'abc-001', 'status' => 'connected', 'duration' => 60])->assertOk();
        $this->postJson($this->url(), ['attempt_id' => 'u-b1', 'app_id' => 'xyz-002', 'status' => 'connected', 'duration' => 600])->assertOk();
        $this->postJson($this->url(), ['attempt_id' => 'u-b2', 'app_id' => 'xyz-002', 'status' => 'connected', 'duration' => 600])->assertOk();

        $this->assertSame(1, $this->withinWorkspace($a, fn () => UsageRecord::count()));

        $this->actingAsClient($a, $userA);

        // One minute, not the twenty-one the other client used.
        $this->get('/usage')->assertOk()->assertSee('1.00')->assertDontSee('20.00');
    }

    public function test_our_wholesale_cost_is_never_serialized(): void
    {
        $this->client('ABC Hospital', 'abc-001', 'a@example.test');

        $this->postJson($this->url(), ['attempt_id' => 'u-cost', 'app_id' => 'abc-001', 'status' => 'connected', 'duration' => 60])->assertOk();

        $usage = UsageRecord::withoutGlobalScope('workspace')->firstOrFail();
        $usage->forceFill(['provider_cost' => '1.2345'])->save();

        $array = $usage->fresh()->toArray();

        $this->assertArrayNotHasKey('provider_cost', $array);
        $this->assertArrayNotHasKey('provider', $array);
        $this->assertArrayHasKey('customer_cost', $array);
    }

    // =================================================================
    // Dashboard and analytics scoping
    // =================================================================

    public function test_dashboard_and_analytics_show_only_the_clients_own_figures(): void
    {
        [$a, $userA, $agentA] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');
        [$b, $_ub, $agentB]   = $this->client('XYZ Realty', 'xyz-002', 'b@example.test');

        $this->completedCall($a, $agentA, ['attempt_id' => 'a-1']);

        foreach (range(1, 4) as $i) {
            $this->completedCall($b, $agentB, [
                'attempt_id' => "b-{$i}",
                'customer'   => "Other {$i}",
                'phone'      => "+9190000033{$i}",
            ]);
        }

        $this->actingAsClient($a, $userA);

        $this->get('/dashboard')->assertOk()->assertSee('Ravi Kumar')->assertDontSee('Other 1');
        $this->get('/analytics')->assertOk()->assertDontSee('Other 1');
        $this->get('/leads?filter=all')->assertOk()->assertSee('Ravi Kumar')->assertDontSee('Other 1');
    }

    public function test_language_breakdown_is_omitted_when_no_language_was_recorded(): void
    {
        [$workspace, $user, $agent] = $this->client('ABC Hospital', 'abc-001', 'a@example.test');

        $this->completedCall($workspace, $agent, ['call' => ['language' => null]]);

        $this->actingAsClient($workspace, $user);

        // Nothing is invented from nulls: with no language data the section goes.
        $this->get('/analytics')->assertOk()->assertDontSee('By language');
    }
}
