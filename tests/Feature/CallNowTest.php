<?php

namespace Tests\Feature;

use App\Models\CallAttempt;
use App\Models\Customer;
use App\Support\CallStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CallNowTest extends TestCase
{
    use RefreshDatabase;

    private function fakeOk(string $attemptId = 'att-new'): void
    {
        Http::fake(['voice.test/*' => Http::response(['attempt_id' => $attemptId], 200)]);
    }

    // =================================================================
    // Access control
    // =================================================================

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/calls', ['phone_number' => '9876543210'])->assertUnauthorized();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    // =================================================================
    // Validation
    // =================================================================

    public function test_it_requires_a_phone_number(): void
    {
        $this->signIn();

        $this->postJson('/calls', [])->assertStatus(422)->assertJsonValidationErrors('phone_number');
    }

    public function test_it_rejects_a_malformed_phone_number(): void
    {
        $this->signIn();

        $this->postJson('/calls', ['phone_number' => '12345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone_number');

        $this->assertDatabaseCount('call_attempts', 0);
    }

    public function test_it_rejects_an_invalid_variable_name(): void
    {
        $this->signIn();

        $this->postJson('/calls', [
            'phone_number' => '9876543210',
            'variables'    => [['key' => '9bad name!', 'value' => 'x']],
        ])->assertStatus(422);
    }

    public function test_it_rejects_an_unsupported_language(): void
    {
        $this->signIn();

        $this->postJson('/calls', ['phone_number' => '9876543210', 'language' => 'Klingon'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('language');
    }

    // =================================================================
    // Placing a call
    // =================================================================

    public function test_it_places_a_call_and_stores_the_attempt_id(): void
    {
        $this->signIn();
        $this->fakeOk('att-xyz');

        $this->postJson('/calls', [
            'phone_number'  => '9876543210',
            'name'          => 'Anita Sharma',
            'policy_number' => 'POL-1',
            'language'      => 'Hindi',
        ])->assertOk()->assertJsonPath('ok', true);

        $attempt = CallAttempt::first();

        $this->assertSame('att-xyz', $attempt->attempt_id);
        $this->assertSame(CallStatus::DISPATCHED, $attempt->status);
        $this->assertSame('+919876543210', $attempt->customer_phone_number);
        $this->assertNotNull($attempt->customer_id, 'a named caller should land in the CRM');
    }

    public function test_the_response_never_contains_the_api_key_or_vendor_endpoint(): void
    {
        $this->signIn();
        $this->fakeOk();

        $response = $this->postJson('/calls', ['phone_number' => '9876543210', 'name' => 'X']);

        $body = $response->getContent();

        $this->assertStringNotContainsString('test-key-not-real', $body);
        $this->assertStringNotContainsString('voice.test', $body);
        $this->assertStringNotContainsString('X-API-Key', $body);
        $this->assertStringNotContainsString('org-test', $body);
    }

    public function test_it_sends_the_customers_mapped_agent_variables(): void
    {
        $this->signIn();
        $this->fakeOk();

        $customer = Customer::create([
            'name'               => 'Anita Sharma',
            'phone_number'       => '+919876543210',
            'policy_number'      => 'POL-2291',
            'preferred_language' => 'Hindi',
        ]);

        $this->postJson('/calls', [
            'customer_id'  => $customer->id,
            'phone_number' => '+919876543210',
        ])->assertOk();

        Http::assertSent(function ($request) {
            $vars = $request->data()['app_config']['agent_variables'];

            return $vars['user_name'] === 'Anita Sharma'
                && $vars['policy_number'] === 'POL-2291';
        });
    }

    public function test_extra_variables_are_forwarded(): void
    {
        $this->signIn();
        $this->fakeOk();

        $this->postJson('/calls', [
            'phone_number' => '9876543210',
            'name'         => 'X',
            'variables'    => [['key' => 'promo_code', 'value' => 'SAVE20']],
        ])->assertOk();

        Http::assertSent(fn ($r) => ($r->data()['app_config']['agent_variables']['promo_code'] ?? null) === 'SAVE20');
    }

    public function test_it_refuses_to_call_a_do_not_call_customer(): void
    {
        $this->signIn();
        $this->fakeOk();

        $customer = Customer::create([
            'name'         => 'Stop',
            'phone_number' => '+919876543210',
            'do_not_call'  => true,
        ]);

        $this->postJson('/calls', [
            'customer_id'  => $customer->id,
            'phone_number' => '+919876543210',
        ])->assertStatus(422)->assertJsonPath('message', 'This customer is marked Do Not Call.');

        Http::assertNothingSent();
    }

    public function test_it_records_a_failed_attempt_when_the_service_errors(): void
    {
        $this->signIn();

        Http::fake(['voice.test/*' => Http::response(['error' => ['code' => 'x', 'message' => 'boom']], 500)]);

        $this->postJson('/calls', ['phone_number' => '9876543210', 'name' => 'X'])
            ->assertStatus(502)
            ->assertJsonPath('ok', false);

        $attempt = CallAttempt::first();

        $this->assertNotNull($attempt, 'the attempt must still be recorded locally');
        $this->assertSame(CallStatus::FAILED, $attempt->status);
        $this->assertNull($attempt->attempt_id);
    }

    public function test_it_reports_when_the_service_is_not_configured(): void
    {
        $this->signIn();
        config(['sarvam.api_key' => null]);

        $this->postJson('/calls', ['phone_number' => '9876543210'])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        Http::assertNothingSent();
    }

    public function test_a_double_submitted_form_only_places_one_call(): void
    {
        $this->signIn();
        $this->fakeOk();

        $payload = [
            'phone_number'    => '9876543210',
            'name'            => 'Anita',
            'idempotency_key' => 'same-key-both-times',
        ];

        $this->postJson('/calls', $payload)->assertOk();
        $this->postJson('/calls', $payload)->assertStatus(429);

        Http::assertSentCount(1);
        $this->assertDatabaseCount('call_attempts', 1);
    }

    public function test_a_bare_number_does_not_manufacture_a_customer(): void
    {
        $this->signIn();
        $this->fakeOk();

        $this->postJson('/calls', ['phone_number' => '9876543210'])->assertOk();

        $this->assertDatabaseCount('customers', 0);
        $this->assertNull(CallAttempt::first()->customer_id);
    }
}
