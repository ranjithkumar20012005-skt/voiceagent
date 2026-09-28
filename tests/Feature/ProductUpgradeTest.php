<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Automation;
use App\Models\CallAttempt;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Support\CallStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Agents, the new insight pages and the redesigned screens: they render real
 * data, route calls to the chosen agent, and never expose a secret.
 */
class ProductUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private function makeCall(array $overrides = []): CallAttempt
    {
        $customer = Customer::create(['name' => 'Meera Nair', 'phone_number' => '9876501234']);

        return CallAttempt::create(array_merge([
            'customer_id'           => $customer->id,
            'attempt_id'            => 'att-' . uniqid(),
            'direction'             => 'outbound',
            'status'                => CallStatus::COMPLETED,
            'connectivity_status'   => CallStatus::CONNECTED,
            'call_disposition'      => CallStatus::INTERESTED,
            'lead_generated'        => true,
            'duration_seconds'      => 125,
            'customer_phone_number' => '+919876501234',
        ], $overrides));
    }

    // =================================================================
    // Agents
    // =================================================================

    public function test_an_agent_can_be_created_and_becomes_the_default(): void
    {
        $this->signIn();

        $this->post('/agents', [
            'name'                 => 'Renewals Hindi',
            'platform_app_id'      => 'Renewal-hi-01',
            'platform_app_version' => 3,
            'default_language'     => 'Hindi',
            'status'               => 'active',
        ])->assertRedirect();

        $agent = Agent::firstOrFail();
        $this->assertTrue($agent->is_default);
        $this->assertSame(['app_id' => 'Renewal-hi-01', 'app_version' => 3], $agent->platformApp());

        $this->get('/agents')->assertOk()->assertSee('Renewals Hindi')->assertSee('Workspace agent');
        $this->get("/agents/{$agent->id}/edit")->assertOk()->assertSee('Renewal-hi-01');
    }

    public function test_an_agent_id_requires_a_version(): void
    {
        $this->signIn();

        $this->post('/agents', ['name' => 'Half set up', 'platform_app_id' => 'abc', 'status' => 'active'])
            ->assertSessionHasErrors('platform_app_version');

        $this->assertDatabaseCount('agents', 0);
    }

    public function test_a_call_uses_the_selected_agents_app(): void
    {
        $this->signIn();
        Http::fake(['voice.test/*' => Http::response(['attempt_id' => 'att-agent'], 200)]);

        $agent = Agent::create(['name' => 'Tamil', 'platform_app_id' => 'App-ta', 'platform_app_version' => 7, 'status' => 'active']);

        $this->postJson('/calls', ['phone_number' => '9876543210', 'agent_id' => $agent->id])
            ->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn (Request $r) => $r['app_config']['app_id'] === 'App-ta'
            && $r['app_config']['app_version'] === 7);

        $this->assertSame($agent->id, CallAttempt::firstOrFail()->agent_id);
    }

    public function test_an_agent_without_its_own_app_falls_back_to_the_workspace_agent(): void
    {
        $this->signIn();
        Http::fake(['voice.test/*' => Http::response(['attempt_id' => 'att-ws'], 200)]);

        $agent = Agent::create(['name' => 'Notes only', 'status' => 'active', 'is_default' => true]);

        $this->postJson('/calls', ['phone_number' => '9876543210'])->assertOk();

        Http::assertSent(fn (Request $r) => $r['app_config']['app_id'] === (string) config('sarvam.app_id'));
        $this->assertSame($agent->id, CallAttempt::firstOrFail()->agent_id);
    }

    public function test_an_inactive_agent_cannot_place_calls(): void
    {
        $this->signIn();
        Http::fake();

        $agent = Agent::create(['name' => 'Retired', 'status' => 'inactive']);

        $this->postJson('/calls', ['phone_number' => '9876543210', 'agent_id' => $agent->id])
            ->assertStatus(422)->assertJsonValidationErrors('agent_id');

        Http::assertNothingSent();
    }

    public function test_an_agent_with_call_history_is_deactivated_not_deleted(): void
    {
        $this->signIn();

        $agent = Agent::create(['name' => 'Busy', 'status' => 'active']);
        $this->makeCall(['agent_id' => $agent->id]);

        $this->delete("/agents/{$agent->id}")->assertRedirect('/agents');

        $this->assertSame('inactive', $agent->fresh()->status);

        $unused = Agent::create(['name' => 'Unused', 'status' => 'active']);
        $this->delete("/agents/{$unused->id}");
        $this->assertModelMissing($unused);
    }

    // =================================================================
    // Pages render with real data
    // =================================================================

    public function test_every_page_renders(): void
    {
        $this->signIn();

        $call     = $this->makeCall(['campaign_id' => 'camp-1', 'callback_at' => now()->addDay()]);
        $customer = $call->customer;
        $customer->update(['next_callback_at' => now()->subHour(), 'last_outcome' => 'interested']);

        $campaign = Campaign::create(['name' => 'October run', 'status' => 'active', 'sarvam_campaign_id' => 'camp-1', 'total_contacts' => 4]);
        $batch    = ImportBatch::create(['original_filename' => 'list.csv', 'status' => ImportBatch::COMPLETED, 'total_rows' => 2, 'valid_rows' => 1, 'rejected_rows' => 1,
                                         'rejection_samples' => [['line' => 3, 'name' => 'X', 'phone' => '12', 'reason' => 'Malformed phone']]]);
        Automation::create(['name' => 'Daily renewals', 'enabled' => true]);
        $agent = Agent::create(['name' => 'Renewals', 'status' => 'active']);

        foreach ([
            '/dashboard' => 'Meera Nair', '/agents' => 'Renewals', '/agents/create' => 'Create Agent',
            "/agents/{$agent->id}/edit" => 'Renewals', '/calling' => 'Instant AI Call',
            '/campaigns' => 'October run', "/campaigns/{$campaign->id}" => 'Not reached',
            '/customers' => 'Meera Nair', "/customers/{$customer->id}" => 'October run',
            '/imports' => 'list.csv', "/imports/{$batch->id}" => 'Malformed phone',
            '/leads' => 'Qualified', '/leads?filter=qualified' => 'Meera Nair',
            '/calls' => 'Meera Nair', "/calls/{$call->id}" => 'Call Details',
            '/callbacks?range=overdue' => 'Overdue', '/automations' => 'Daily renewals',
            '/analytics' => 'Campaign performance', '/usage' => 'Conversation minutes',
            '/providers' => 'Speech-to-Text', '/phone-numbers' => 'Numbers',
            '/knowledge-base' => 'Not connected', '/tools' => 'Not connected', '/settings' => 'Calling defaults',
        ] as $url => $text) {
            $this->get($url)->assertOk()->assertSee($text, false);
        }
    }

    public function test_analytics_counts_are_real_aggregates(): void
    {
        $this->signIn();

        $this->makeCall();
        $this->makeCall(['connectivity_status' => CallStatus::NO_ANSWER, 'call_disposition' => null, 'lead_generated' => false, 'duration_seconds' => null]);

        $totals = app(\App\Services\DashboardMetrics::class)->totals();

        $this->assertSame(2, $totals['total_calls']);
        $this->assertSame(1, $totals['connected']);
        $this->assertSame(1, $totals['no_answer']);
        $this->assertSame(1, $totals['qualified']);
        $this->assertSame(50.0, $totals['connect_rate']);
    }

    public function test_the_empty_analytics_page_says_so(): void
    {
        $this->signIn();

        $this->get('/analytics')->assertOk()->assertSee('No calls in this period');
    }

    public function test_new_pages_require_authentication(): void
    {
        foreach (['/agents', '/analytics', '/usage', '/providers', '/phone-numbers', '/knowledge-base', '/tools'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    // =================================================================
    // Secrets never reach a page
    // =================================================================

    public function test_admin_pages_never_render_credentials(): void
    {
        config([
            'app.debug'            => false,
            'sarvam.api_key'       => 'sk-super-secret-key-123',
            'sarvam.webhook_token' => 'whk-secret-token-456789',
            'sarvam.org_id'        => 'org-private-778',
            'sarvam.workspace_id'  => 'ws-private-889',
            'sarvam.connection_id' => 'conn-private-990',
        ]);
        $this->app->forgetInstance(\App\Services\SarvamVoiceService::class);

        $this->signIn();
        $this->makeCall();

        foreach (['/agents', '/agents/create', '/providers', '/phone-numbers', '/settings', '/analytics', '/usage', '/knowledge-base', '/tools', '/campaigns', '/customers', '/callbacks', '/automations', '/imports'] as $url) {
            $body = $this->get($url)->assertOk()->getContent();

            foreach (['sk-super-secret-key-123', 'whk-secret-token-456789', 'org-private-778', 'ws-private-889', 'conn-private-990'] as $secret) {
                $this->assertStringNotContainsString($secret, $body, "A secret leaked on {$url}");
            }
        }
    }
}
