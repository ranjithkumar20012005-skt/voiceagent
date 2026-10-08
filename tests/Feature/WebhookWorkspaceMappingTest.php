<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\CallAttempt;
use App\Models\Customer;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A call result has to land in the right client's dashboard.
 *
 * Each client has their own hosted agent, so the agent identifier in the
 * callback is what says whose call it was. If that mapping is wrong, one client
 * sees another's conversations -- which is why this is tested by sending the same
 * shaped payload for two different agents and checking where each one lands.
 */
class WebhookWorkspaceMappingTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-webhook-token-0123456789';

    private function url(): string
    {
        return '/api/webhooks/sarvam/' . self::TOKEN;
    }

    /** A client with their own hosted agent mapped. */
    private function clientWithAgent(string $name, string $providerAgentId, string $email): array
    {
        [$workspace, $user] = $this->makeClient($name, $email);

        $agent = $this->withinWorkspace($workspace, function () use ($name, $providerAgentId) {
            $agent = Agent::create(['name' => $name . ' agent', 'status' => Agent::ACTIVE]);
            $agent->forceFill([
                'provider_agent_id'      => $providerAgentId,
                'provider_agent_version' => 1,
            ])->save();

            return $agent;
        });

        return [$workspace, $user, $agent];
    }

    public function test_a_result_lands_in_the_workspace_that_owns_the_agent(): void
    {
        [$a, $_ua, $agentA] = $this->clientWithAgent('ABC Hospital', 'abc-agent-001', 'a@example.test');
        [$b, $_ub, $agentB] = $this->clientWithAgent('XYZ Realty', 'xyz-agent-002', 'b@example.test');

        $this->postJson($this->url(), [
            'attempt_id' => 'att-for-a',
            'app_id'     => 'abc-agent-001',
            'status'     => 'connected',
            'duration'   => 90,
        ])->assertOk();

        $this->postJson($this->url(), [
            'attempt_id' => 'att-for-b',
            'app_id'     => 'xyz-agent-002',
            'status'     => 'connected',
            'duration'   => 45,
        ])->assertOk();

        $callA = CallAttempt::withoutGlobalScope('workspace')->firstWhere('attempt_id', 'att-for-a');
        $callB = CallAttempt::withoutGlobalScope('workspace')->firstWhere('attempt_id', 'att-for-b');

        $this->assertSame($a->id, $callA->workspace_id);
        $this->assertSame($agentA->id, $callA->agent_id);

        $this->assertSame($b->id, $callB->workspace_id);
        $this->assertSame($agentB->id, $callB->agent_id);
    }

    public function test_a_client_only_sees_the_results_of_their_own_calls(): void
    {
        [$a, $userA] = $this->clientWithAgent('ABC Hospital', 'abc-agent-001', 'a@example.test');
        $this->clientWithAgent('XYZ Realty', 'xyz-agent-002', 'b@example.test');

        // One result for each client.
        $this->postJson($this->url(), ['attempt_id' => 'att-a', 'app_id' => 'abc-agent-001', 'status' => 'connected'])->assertOk();
        $this->postJson($this->url(), ['attempt_id' => 'att-b', 'app_id' => 'xyz-agent-002', 'status' => 'connected'])->assertOk();

        $this->actingAsClient($a, $userA);

        $this->get('/calls')->assertOk()->assertDontSee('att-b');

        $this->assertSame(1, $this->withinWorkspace($a, fn () => CallAttempt::count()));
        $this->assertSame('att-a', $this->withinWorkspace($a, fn () => CallAttempt::first()->attempt_id));
    }

    public function test_an_unknown_agent_is_rejected(): void
    {
        $this->clientWithAgent('ABC Hospital', 'abc-agent-001', 'a@example.test');

        $this->postJson($this->url(), [
            'attempt_id' => 'att-stranger',
            'app_id'     => 'not-one-of-ours',
            'status'     => 'connected',
        ])->assertStatus(422);

        $this->assertSame(0, CallAttempt::withoutGlobalScope('workspace')->count());
    }

    public function test_a_duplicate_delivery_does_not_create_a_second_call(): void
    {
        [$a] = $this->clientWithAgent('ABC Hospital', 'abc-agent-001', 'a@example.test');

        $payload = [
            'attempt_id' => 'att-once',
            'app_id'     => 'abc-agent-001',
            'status'     => 'connected',
            'duration'   => 120,
            'final_agent_variables' => ['call_disposition' => 'interested'],
        ];

        $first  = $this->postJson($this->url(), $payload)->assertOk();
        $second = $this->postJson($this->url(), $payload)->assertOk();

        $first->assertJson(['ok' => true, 'duplicate' => false]);
        $second->assertJson(['ok' => true, 'duplicate' => true]);

        $this->assertSame(1, CallAttempt::withoutGlobalScope('workspace')->where('attempt_id', 'att-once')->count());
        $this->assertSame(1, CallAttempt::withoutGlobalScope('workspace')->count());
    }

    public function test_a_replay_does_not_double_count_the_customers_calls(): void
    {
        [$a] = $this->clientWithAgent('ABC Hospital', 'abc-agent-001', 'a@example.test');

        $customer = $this->withinWorkspace($a, fn () => Customer::create([
            'customer_identifier' => 'CUST-77',
            'name'                => 'Meera',
            'phone_number'        => '+919000000077',
        ]));

        $payload = [
            'attempt_id'      => 'att-replay',
            'app_id'          => 'abc-agent-001',
            'status'          => 'connected',
            'user_identifier' => 'CUST-77',
            'duration'        => 60,
        ];

        $this->postJson($this->url(), $payload)->assertOk();
        $this->postJson($this->url(), $payload)->assertOk();
        $this->postJson($this->url(), $payload)->assertOk();

        $this->assertSame(1, (int) $customer->fresh()->call_count, 'Customer state moves forward once, however many times a result is delivered.');
    }

    public function test_a_callback_with_no_agent_falls_back_to_the_default_workspace(): void
    {
        // The single-agent setup that existed before clients had their own agents:
        // the environment agent still has to work.
        config(['sarvam.app_id' => 'env-agent-legacy']);

        $this->postJson($this->url(), [
            'attempt_id' => 'att-legacy',
            'app_id'     => 'env-agent-legacy',
            'status'     => 'connected',
        ])->assertOk();

        $call    = CallAttempt::withoutGlobalScope('workspace')->firstWhere('attempt_id', 'att-legacy');
        $default = Workspace::where('slug', 'default')->firstOrFail();

        $this->assertSame($default->id, $call->workspace_id);
        $this->assertNull($call->agent_id);
    }

    public function test_a_bad_token_is_still_refused(): void
    {
        $this->clientWithAgent('ABC Hospital', 'abc-agent-001', 'a@example.test');

        $this->postJson('/api/webhooks/sarvam/wrong-token', [
            'attempt_id' => 'att-x',
            'app_id'     => 'abc-agent-001',
        ])->assertNotFound();

        $this->assertSame(0, CallAttempt::withoutGlobalScope('workspace')->count());
    }
}
