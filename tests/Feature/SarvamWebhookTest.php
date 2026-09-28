<?php

namespace Tests\Feature;

use App\Models\CallAttempt;
use App\Models\Customer;
use App\Support\CallStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SarvamWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-webhook-token-0123456789';

    private function url(?string $token = null): string
    {
        return '/api/webhooks/sarvam/' . ($token ?? self::TOKEN);
    }

    private function instantPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'attempt_id'   => 'att-1',
            'status'       => 'connected',
            'duration'     => 120,
            'interaction_id' => 'int-1',
            'channel_info' => ['channel_type' => 'v2v', 'agent_phone_number' => '+918000000000'],
            'final_agent_variables' => ['call_disposition' => 'interested'],
            'webhook_config' => ['url' => 'https://app.test', 'metadata' => []],
            'interaction_transcript' => [
                ['role' => 'agent', 'en_text' => 'Hello'],
                ['role' => 'user',  'en_text' => 'Hi'],
            ],
        ], $overrides);
    }

    // =================================================================
    // Authentication & validation
    // =================================================================

    public function test_it_rejects_a_wrong_token(): void
    {
        $this->postJson($this->url('wrong-token-aaaaaaaaaaaaaaaa'), $this->instantPayload())
            ->assertStatus(404);

        $this->assertDatabaseCount('call_attempts', 0);
    }

    public function test_a_blank_configured_token_rejects_everything(): void
    {
        config(['sarvam.webhook_token' => '']);

        $this->postJson($this->url(''), $this->instantPayload())->assertStatus(404);
        $this->postJson($this->url('anything-at-all-1234567890'), $this->instantPayload())->assertStatus(404);
    }

    public function test_it_requires_an_attempt_id(): void
    {
        $this->postJson($this->url(), ['status' => 'connected'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'attempt_id is required');
    }

    public function test_it_rejects_an_empty_payload(): void
    {
        $this->postJson($this->url(), [])->assertStatus(422);
    }

    public function test_it_rejects_a_payload_for_another_app(): void
    {
        $this->postJson($this->url(), $this->instantPayload(['app_id' => 'someone-elses-app']))
            ->assertStatus(422)
            ->assertJsonPath('error', 'unknown app');
    }

    public function test_it_accepts_a_payload_for_our_app(): void
    {
        $this->postJson($this->url(), $this->instantPayload(['app_id' => 'app-test']))
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_it_rejects_a_malformed_transcript(): void
    {
        $this->postJson($this->url(), $this->instantPayload(['interaction_transcript' => 'not-an-array']))
            ->assertStatus(422);
    }

    public function test_it_does_not_require_authentication(): void
    {
        // No signed-in user -- the endpoint must still work.
        $this->assertGuest();

        $this->postJson($this->url(), $this->instantPayload())->assertOk();
    }

    // =================================================================
    // Idempotency
    // =================================================================

    public function test_replaying_the_same_webhook_does_not_duplicate_the_attempt(): void
    {
        $payload = $this->instantPayload();

        $this->postJson($this->url(), $payload)->assertOk()->assertJsonPath('duplicate', false);
        $this->postJson($this->url(), $payload)->assertOk()->assertJsonPath('duplicate', true);
        $this->postJson($this->url(), $payload)->assertOk()->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('call_attempts', 1);
    }

    public function test_replaying_does_not_double_count_the_customer(): void
    {
        $customer = Customer::create([
            'name'         => 'Anita',
            'phone_number' => '+919876543210',
        ]);

        $payload = $this->instantPayload(['user_phone_number' => '+919876543210']);

        $this->postJson($this->url(), $payload)->assertOk();
        $this->postJson($this->url(), $payload)->assertOk();
        $this->postJson($this->url(), $payload)->assertOk();

        $customer->refresh();

        $this->assertSame(1, $customer->call_count, 'call_count must only increment on the first delivery');
    }

    public function test_it_updates_an_attempt_we_already_created_locally(): void
    {
        $customer = Customer::create(['name' => 'Anita', 'phone_number' => '+919876543210']);

        $attempt = CallAttempt::create([
            'customer_id' => $customer->id,
            'attempt_id'  => 'att-1',
            'status'      => CallStatus::DISPATCHED,
        ]);

        $this->postJson($this->url(), $this->instantPayload())->assertOk()->assertJsonPath('duplicate', false);

        $this->assertDatabaseCount('call_attempts', 1);

        $attempt->refresh();
        $this->assertSame(CallStatus::COMPLETED, $attempt->status);
        $this->assertSame(CallStatus::CONNECTED, $attempt->connectivity_status);
        $this->assertSame($customer->id, $attempt->customer_id);
    }

    // =================================================================
    // Mapping
    // =================================================================

    public function test_it_maps_connectivity_and_disposition(): void
    {
        $this->postJson($this->url(), $this->instantPayload())->assertOk();

        $attempt = CallAttempt::firstWhere('attempt_id', 'att-1');

        $this->assertSame(CallStatus::CONNECTED, $attempt->connectivity_status);
        $this->assertSame(CallStatus::INTERESTED, $attempt->call_disposition);
        $this->assertSame(120, $attempt->duration_seconds);
        $this->assertSame('int-1', $attempt->interaction_id);
        $this->assertCount(2, $attempt->transcript);
    }

    public function test_campaign_payload_fields_are_stored(): void
    {
        $this->postJson($this->url(), [
            'app_id'              => 'app-test',
            'attempt_id'          => 'att-camp',
            'campaign_id'         => 'camp-1',
            'cohort_id'           => 'coh-1',
            'completion_status'   => 'completed',
            'connectivity_status' => 'no_answer',
            'user_identifier'     => 'CUST-9',
            'user_phone_number'   => '+919876543210',
            'agent_phone_number'  => '+918000000000',
            'retry_attempt'       => 2,
            'duration'            => null,
            'start_datetime'      => '2026-09-21T09:15:00+05:30',
            'end_datetime'        => '2026-09-21T09:16:00+05:30',
            'output_agent_variables' => ['call_disposition' => 'not_interested'],
        ])->assertOk();

        $attempt = CallAttempt::firstWhere('attempt_id', 'att-camp');

        $this->assertSame('camp-1', $attempt->campaign_id);
        $this->assertSame('coh-1', $attempt->cohort_id);
        $this->assertSame('completed', $attempt->completion_status);
        $this->assertSame(CallStatus::NO_ANSWER, $attempt->connectivity_status);
        $this->assertSame(CallStatus::NOT_INTERESTED, $attempt->call_disposition);
        $this->assertSame(2, $attempt->retry_attempt);
        $this->assertNotNull($attempt->started_at);
    }

    public function test_it_links_a_customer_by_user_identifier(): void
    {
        $customer = Customer::create([
            'customer_identifier' => 'CUST-9',
            'name'                => 'Priya',
            'phone_number'        => '+919000000001',
        ]);

        $this->postJson($this->url(), [
            'attempt_id'      => 'att-ident',
            'status'          => 'connected',
            'user_identifier' => 'CUST-9',
        ])->assertOk();

        $this->assertSame($customer->id, CallAttempt::firstWhere('attempt_id', 'att-ident')->customer_id);
    }

    public function test_it_links_a_customer_by_webhook_metadata(): void
    {
        $customer = Customer::create(['name' => 'Meta', 'phone_number' => '+919000000002']);

        $this->postJson($this->url(), $this->instantPayload([
            'attempt_id'     => 'att-meta',
            'webhook_config' => ['metadata' => ['customer_id' => (string) $customer->id]],
        ]))->assertOk();

        $this->assertSame($customer->id, CallAttempt::firstWhere('attempt_id', 'att-meta')->customer_id);
    }

    public function test_an_unrecognised_disposition_becomes_unknown_not_negative(): void
    {
        $this->postJson($this->url(), $this->instantPayload([
            'attempt_id'            => 'att-weird',
            'final_agent_variables' => ['call_disposition' => 'something we have never seen'],
        ]))->assertOk();

        $attempt = CallAttempt::firstWhere('attempt_id', 'att-weird');

        $this->assertSame(CallStatus::UNKNOWN, $attempt->call_disposition);
        $this->assertNotSame(CallStatus::NOT_INTERESTED, $attempt->call_disposition);
    }

    public function test_a_connected_call_with_no_output_variables_is_unknown(): void
    {
        $this->postJson($this->url(), [
            'attempt_id' => 'att-silent',
            'status'     => 'connected',
        ])->assertOk();

        $this->assertSame(CallStatus::UNKNOWN, CallAttempt::firstWhere('attempt_id', 'att-silent')->call_disposition);
    }

    public function test_a_callback_sets_the_customer_callback_date(): void
    {
        $customer = Customer::create(['name' => 'Rahul', 'phone_number' => '+919876543211']);

        $this->postJson($this->url(), [
            'attempt_id'        => 'att-cb',
            'status'            => 'connected',
            'user_phone_number' => '+919876543211',
            'output_agent_variables' => [
                'call_disposition'  => 'callback',
                'callback_required' => true,
                'callback_at'       => '2026-09-24T11:00:00+05:30',
            ],
        ])->assertOk();

        $customer->refresh();

        $this->assertTrue($customer->next_callback_at->isSameDay('2026-09-24'));
        $this->assertSame(CallStatus::CALLBACK, $customer->last_outcome);
    }

    public function test_a_do_not_call_disposition_flags_the_customer(): void
    {
        $customer = Customer::create(['name' => 'Stop', 'phone_number' => '+919876543212']);

        $this->postJson($this->url(), [
            'attempt_id'             => 'att-dnc',
            'status'                 => 'connected',
            'user_phone_number'      => '+919876543212',
            'output_agent_variables' => ['call_disposition' => 'do_not_call'],
        ])->assertOk();

        $this->assertTrue($customer->fresh()->do_not_call);
    }

    public function test_the_raw_payload_is_retained(): void
    {
        $this->postJson($this->url(), $this->instantPayload())->assertOk();

        $attempt = CallAttempt::firstWhere('attempt_id', 'att-1');

        $this->assertIsArray($attempt->raw_webhook_payload);
        $this->assertSame('att-1', $attempt->raw_webhook_payload['attempt_id']);
        $this->assertNotNull($attempt->webhook_received_at);
    }
}
