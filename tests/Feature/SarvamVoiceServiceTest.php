<?php

namespace Tests\Feature;

use App\Services\SarvamException;
use App\Services\SarvamVoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The service is exercised entirely against mocked HTTP responses -- these
 * tests never touch a real voice platform.
 */
class SarvamVoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SarvamVoiceService
    {
        return SarvamVoiceService::make();
    }

    // =================================================================
    // Configuration
    // =================================================================

    public function test_it_reports_configured_when_all_settings_are_present(): void
    {
        $this->assertTrue($this->service()->isConfigured());
        $this->assertSame([], $this->service()->missingConfigKeys());
    }

    public function test_it_reports_missing_keys_by_name_only(): void
    {
        config(['sarvam.api_key' => null, 'sarvam.app_id' => null]);

        $missing = $this->service()->missingConfigKeys();

        $this->assertContains('SARVAM_API_KEY', $missing);
        $this->assertContains('SARVAM_APP_ID', $missing);
        $this->assertFalse($this->service()->isConfigured());
    }

    public function test_public_status_never_exposes_the_api_key(): void
    {
        $status = $this->service()->publicStatus();

        $this->assertArrayNotHasKey('api_key', $status);
        $this->assertStringNotContainsString('test-key-not-real', json_encode($status));
    }

    public function test_it_refuses_to_call_when_not_configured(): void
    {
        config(['sarvam.api_key' => null]);

        $this->expectException(SarvamException::class);
        $this->expectExceptionMessage('not configured');

        $this->service()->createInstantCall('+919876543210');
    }

    // =================================================================
    // Instant outbound
    // =================================================================

    public function test_it_creates_an_instant_call_with_the_documented_payload(): void
    {
        Http::fake([
            'voice.test/*' => Http::response(['attempt_id' => 'att-123'], 200),
        ]);

        $result = $this->service()->createInstantCall(
            phoneNumber: '+919876543210',
            agentVariables: ['user_name' => 'Anita', 'policy_number' => 'POL-1'],
            language: 'Hindi',
            webhookMetadata: ['customer_id' => '7'],
        );

        $this->assertSame('att-123', $result['attempt_id']);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return $request->method() === 'POST'
                && str_contains($request->url(), '/api/outbounds/v1/orgs/org-test/workspaces/ws-test/outbounds')
                // Correct auth header for the Voice Agents API.
                && $request->hasHeader('X-API-Key', 'test-key-not-real')
                // Documented body shape.
                && $body['app_config']['app_id'] === 'app-test'
                && $body['app_config']['app_version'] === 1
                && $body['app_config']['connection_config']['connection_id'] === 'conn-test'
                && $body['app_config']['connection_config']['agent_phone_number'] === '+918000000000'
                && $body['app_config']['agent_variables']['user_name'] === 'Anita'
                && $body['app_config']['app_overrides']['initial_language_name'] === 'Hindi'
                && $body['user_config']['user_phone_number'] === '+919876543210'
                && $body['webhook_config']['url'] === 'https://app.test/api/webhooks/sarvam/test-webhook-token-0123456789'
                && $body['webhook_config']['metadata']['customer_id'] === '7';
        });
    }

    public function test_it_throws_when_no_attempt_id_is_returned(): void
    {
        Http::fake(['voice.test/*' => Http::response(['unexpected' => true], 200)]);

        $this->expectException(SarvamException::class);
        $this->expectExceptionMessage('did not return an attempt_id');

        $this->service()->createInstantCall('+919876543210');
    }

    public function test_it_surfaces_a_validation_error_message(): void
    {
        Http::fake([
            'voice.test/*' => Http::response([
                'detail' => [['loc' => ['body', 'user_config'], 'msg' => 'field required', 'type' => 'missing']],
            ], 422),
        ]);

        try {
            $this->service()->createInstantCall('+919876543210');
            $this->fail('Expected SarvamException.');
        } catch (SarvamException $e) {
            $this->assertSame(422, $e->status);
            $this->assertStringContainsString('field required', $e->getMessage());
            $this->assertStringContainsString('Voice agent configuration is invalid', $e->userMessage());
            $this->assertSame(422, $e->responseStatus());
        }
    }

    public function test_auth_failure_is_reported_without_leaking_the_key(): void
    {
        // apps.sarvam.ai returns 401 with this envelope for a bad key.
        Http::fake([
            'voice.test/*' => Http::response([
                'error' => [
                    'message' => '(401) Unauthorized',
                    'type'    => 'unauthorized',
                    'code'    => 401,
                    'data'    => ['details' => 'Invalid API key format.'],
                ],
            ], 401),
        ]);

        try {
            $this->service()->createInstantCall('+919876543210');
            $this->fail('Expected SarvamException.');
        } catch (SarvamException $e) {
            $this->assertSame(401, $e->status);
            $this->assertStringNotContainsString('test-key-not-real', $e->getMessage());
            $this->assertStringNotContainsString('test-key-not-real', $e->userMessage());
            $this->assertSame('Invalid or missing Sarvam API key.', $e->userMessage());
            // A bad key is our misconfiguration, not a gateway fault.
            $this->assertSame(500, $e->responseStatus());
        }
    }

    public function test_a_client_error_is_not_retried(): void
    {
        Http::fake(['voice.test/*' => Http::response(['error' => ['code' => 'x', 'message' => 'nope']], 422)]);

        try {
            $this->service()->createInstantCall('+919876543210');
        } catch (SarvamException) {
            // expected
        }

        // A rejected call must never be resubmitted.
        Http::assertSentCount(1);
    }

    // =================================================================
    // Campaigns & cohorts
    // =================================================================

    public function test_it_creates_a_campaign_with_clamped_concurrency(): void
    {
        Http::fake([
            'voice.test/*' => Http::response(['campaign_id' => 'camp-1', 'status' => 'scheduled'], 200),
        ]);

        $result = $this->service()->createCampaign(
            name: 'October Renewals',
            startsAt: now(),
            endsAt: now()->addDay(),
            attemptsPerSecond: 9999.0,   // deliberately absurd
        );

        $this->assertSame('camp-1', $result['campaign_id']);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return str_contains($request->url(), '/api/scheduling/v1/orgs/org-test/workspaces/ws-test/campaigns')
                && $body['app_config']['attempts_per_second'] === 500.0     // clamped to the documented max
                && $body['allowed_schedule']['timezone'] === 'Asia/Kolkata'
                && isset($body['app_config']['retry_config']['retry_on']['busy']['enabled']);
        });
    }

    public function test_it_streams_a_cohort(): void
    {
        Http::fake([
            'voice.test/*' => Http::response([
                'cohort_id' => 'coh-1',
                'status'    => 'processing',
                'result'    => ['total_records' => 2, 'valid_records' => 2, 'rejected_records' => 0],
            ], 200),
        ]);

        $result = $this->service()->streamCohort('camp-1', 'October Renewals part 1', [
            ['user_phone_number' => '+919876543210', 'user_identifier' => 'CUST-1'],
            ['user_phone_number' => '+919876543211', 'user_identifier' => 'CUST-2'],
        ]);

        $this->assertSame('coh-1', $result['cohort_id']);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/campaigns/camp-1/cohorts/stream')
            && count($r->data()['users']) === 2);
    }

    public function test_it_rejects_a_cohort_over_the_documented_limit(): void
    {
        $users = array_fill(0, 1001, ['user_phone_number' => '+919876543210']);

        $this->expectException(SarvamException::class);
        $this->expectExceptionMessage('exceeds the 1000-contact limit');

        $this->service()->streamCohort('camp-1', 'too big', $users);
    }

    public function test_it_sanitises_a_cohort_name(): void
    {
        Http::fake(['voice.test/*' => Http::response(['cohort_id' => 'coh-1'], 200)]);

        $this->service()->streamCohort('camp-1', 'Bad/Name: <script>!! ' . str_repeat('x', 80), [
            ['user_phone_number' => '+919876543210'],
        ]);

        Http::assertSent(function (Request $r) {
            $name = $r->data()['name'];

            return strlen($name) <= 50 && preg_match('/^[A-Za-z0-9 _-]+$/', $name) === 1;
        });
    }

    public function test_it_rejects_an_unsupported_campaign_action(): void
    {
        $this->expectException(SarvamException::class);

        $this->service()->updateCampaignStatus('camp-1', 'destroy');
    }

    public function test_it_updates_campaign_status(): void
    {
        Http::fake(['voice.test/*' => Http::response(['campaign_id' => 'camp-1', 'status' => 'paused'], 200)]);

        $result = $this->service()->updateCampaignStatus('camp-1', 'pause');

        $this->assertSame('paused', $result['status']);

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && str_contains($r->url(), '/campaigns/camp-1/status')
            && $r->data()['action'] === 'pause');
    }

    public function test_webhook_url_is_null_without_a_token(): void
    {
        config(['sarvam.webhook_token' => null]);

        $this->assertNull($this->service()->webhookUrl());
    }
}
