<?php

namespace Tests\Feature;

use App\Jobs\DispatchCampaignJob;
use App\Models\Automation;
use App\Models\Campaign;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationDispatchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pin the tenant for these fixtures.
     *
     * A schedule belongs to a client now: the dispatcher runs each one as its own
     * workspace, and one with none is skipped rather than being allowed to select
     * across every client's customers. So the fixtures need a workspace, and the
     * default one is where a callback with no agent mapping also lands.
     */
    protected function setUp(): void
    {
        parent::setUp();

        app(\App\Support\Tenancy::class)->set(
            \App\Models\Workspace::where('slug', 'default')->first() ?? $this->makeWorkspace('Default Workspace'),
        );
    }

    private function automation(array $overrides = []): Automation
    {
        return Automation::create(array_merge([
            'name'                   => 'Daily Health Insurance Renewal Calls',
            'enabled'                => true,
            'frequency'              => 'daily',
            'run_at'                 => '08:00',
            'timezone'               => 'Asia/Kolkata',
            'expiry_within_days'     => 30,
            'customer_statuses'      => ['pending'],
            'skip_do_not_call'       => true,
            'skip_already_renewed'   => true,
            'skip_active_callback'   => true,
            'min_days_between_calls' => 1,
            'max_calls_per_run'      => 200,
            'max_retries'            => 2,
            'window_start'           => '00:00',
            'window_end'             => '23:59',
            'attempts_per_second'    => 1.0,
        ], $overrides));
    }

    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'name'               => 'Eligible Person',
            'phone_number'       => '+9198765' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT),
            'policy_expiry_date' => now()->addDays(10)->toDateString(),
            'customer_status'    => 'pending',
        ], $overrides));
    }

    // =================================================================
    // Eligibility
    // =================================================================

    public function test_it_selects_a_customer_whose_policy_is_due(): void
    {
        $automation = $this->automation();
        $customer   = $this->customer();

        $this->assertTrue($automation->eligibleCustomers()->pluck('id')->contains($customer->id));
    }

    public function test_it_never_selects_a_do_not_call_customer(): void
    {
        $automation = $this->automation();
        $this->customer(['do_not_call' => true]);

        $this->assertSame(0, $automation->eligibleCustomers()->count());
    }

    public function test_it_skips_a_policy_expiring_beyond_the_window(): void
    {
        $automation = $this->automation(['expiry_within_days' => 7]);
        $this->customer(['policy_expiry_date' => now()->addDays(60)->toDateString()]);

        $this->assertSame(0, $automation->eligibleCustomers()->count());
    }

    public function test_it_skips_an_already_expired_policy(): void
    {
        $automation = $this->automation();
        $this->customer(['policy_expiry_date' => now()->subDay()->toDateString()]);

        $this->assertSame(0, $automation->eligibleCustomers()->count());
    }

    public function test_it_skips_a_customer_with_a_pending_callback(): void
    {
        $automation = $this->automation();
        $this->customer(['next_callback_at' => now()->addDays(3)]);

        $this->assertSame(0, $automation->eligibleCustomers()->count());
    }

    public function test_it_includes_a_customer_whose_callback_is_now_due(): void
    {
        $automation = $this->automation();
        $this->customer(['next_callback_at' => now()->subHour()]);

        $this->assertSame(1, $automation->eligibleCustomers()->count());
    }

    public function test_it_skips_an_already_renewed_customer(): void
    {
        $automation = $this->automation();
        $this->customer(['last_outcome' => 'already_renewed']);

        $this->assertSame(0, $automation->eligibleCustomers()->count());
    }

    public function test_it_respects_the_minimum_gap_between_calls(): void
    {
        $automation = $this->automation(['min_days_between_calls' => 3]);
        $this->customer(['last_call_at' => now()->subDay()]);

        $this->assertSame(0, $automation->eligibleCustomers()->count());
    }

    public function test_it_respects_the_calling_window(): void
    {
        $automation = $this->automation(['window_start' => '09:00', 'window_end' => '09:01']);

        $this->travelTo(now()->setTimezone('Asia/Kolkata')->setTime(14, 0));
        $this->assertFalse($automation->withinWindow());

        $this->travelTo(now()->setTimezone('Asia/Kolkata')->setTime(9, 0));
        $this->assertTrue($automation->withinWindow());
    }

    // =================================================================
    // The scheduled command
    // =================================================================

    public function test_the_command_is_registered(): void
    {
        $this->artisan('calls:dispatch-due --dry-run')->assertSuccessful();
    }

    public function test_a_dry_run_dispatches_nothing(): void
    {
        Queue::fake();

        $this->automation();
        $this->customer();

        $this->artisan('calls:dispatch-due --dry-run')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_a_real_run_queues_a_campaign(): void
    {
        Queue::fake();

        $automation = $this->automation();
        $this->customer();

        $this->artisan('calls:dispatch-due')->assertSuccessful();

        Queue::assertPushed(DispatchCampaignJob::class);

        $this->assertDatabaseCount('campaigns', 1);

        $automation->refresh();
        $this->assertSame('dispatched', $automation->last_run_status);
        $this->assertSame(1, $automation->last_run_count);
    }

    public function test_it_skips_a_disabled_automation(): void
    {
        Queue::fake();

        $this->automation(['enabled' => false]);
        $this->customer();

        $this->artisan('calls:dispatch-due')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_it_caps_the_number_of_calls_per_run(): void
    {
        Queue::fake();

        $this->automation(['max_calls_per_run' => 2]);

        for ($i = 0; $i < 5; $i++) {
            $this->customer(['phone_number' => '+91987654321' . $i]);
        }

        $this->artisan('calls:dispatch-due')->assertSuccessful();

        $this->assertSame(2, Campaign::first()->total_contacts);
    }

    public function test_it_fails_cleanly_when_the_service_is_unconfigured(): void
    {
        config(['sarvam.api_key' => null]);

        $this->artisan('calls:dispatch-due')->assertFailed();
    }

    // =================================================================
    // Dispatch job
    // =================================================================

    public function test_the_dispatch_job_creates_a_campaign_and_streams_a_cohort(): void
    {
        Http::fake([
            'voice.test/*/campaigns' => Http::response(['campaign_id' => 'camp-1', 'status' => 'active'], 200),
            'voice.test/*/cohorts/stream' => Http::response([
                'cohort_id' => 'coh-1',
                'status'    => 'processing',
                'result'    => ['total_records' => 1, 'valid_records' => 1, 'rejected_records' => 0],
            ], 200),
        ]);

        $customer = $this->customer();

        $campaign = Campaign::create([
            'name'           => 'Test Run',
            'status'         => 'draft',
            'total_contacts' => 1,
        ]);

        (new DispatchCampaignJob($campaign->id, [$customer->id]))->handle(
            app(\App\Services\SarvamVoiceService::class)
        );

        $campaign->refresh();

        $this->assertSame('camp-1', $campaign->sarvam_campaign_id);
        $this->assertSame('active', $campaign->status);
        $this->assertContains('coh-1', $campaign->cohort_ids);
        $this->assertSame(1, $campaign->dispatched_contacts);

        // A local placeholder attempt exists so the dashboard shows work in flight.
        $this->assertDatabaseHas('call_attempts', [
            'customer_id' => $customer->id,
            'campaign_id' => 'camp-1',
            'status'      => 'dispatched',
        ]);

        $this->assertSame('queued', $customer->fresh()->customer_status);
    }

    public function test_a_configured_campaign_id_is_reused_instead_of_creating_one(): void
    {
        config(['sarvam.campaign_id' => 'preconfigured-camp']);

        Http::fake([
            'voice.test/*/cohorts/stream' => Http::response(['cohort_id' => 'coh-9'], 200),
        ]);

        $customer = $this->customer();
        $campaign = Campaign::create(['name' => 'Reuse', 'status' => 'draft', 'total_contacts' => 1]);

        (new DispatchCampaignJob($campaign->id, [$customer->id]))->handle(
            app(\App\Services\SarvamVoiceService::class)
        );

        $campaign->refresh();

        $this->assertSame('preconfigured-camp', $campaign->sarvam_campaign_id);

        // No campaign was created remotely -- only the cohort stream was called.
        Http::assertSent(fn ($r) => str_contains($r->url(), 'cohorts/stream'));
        Http::assertSentCount(1);
    }

    public function test_the_dispatch_job_excludes_do_not_call_customers(): void
    {
        Http::fake([
            'voice.test/*/campaigns' => Http::response(['campaign_id' => 'camp-1', 'status' => 'active'], 200),
            'voice.test/*/cohorts/stream' => Http::response(['cohort_id' => 'coh-1'], 200),
        ]);

        $ok  = $this->customer();
        $dnc = $this->customer(['do_not_call' => true]);

        $campaign = Campaign::create(['name' => 'Test', 'status' => 'draft', 'total_contacts' => 2]);

        (new DispatchCampaignJob($campaign->id, [$ok->id, $dnc->id]))->handle(
            app(\App\Services\SarvamVoiceService::class)
        );

        Http::assertSent(function ($request) use ($ok) {
            if (! str_contains($request->url(), 'cohorts/stream')) {
                return true;
            }

            $phones = array_column($request->data()['users'], 'user_phone_number');

            return $phones === [$ok->phone_number];
        });
    }

    public function test_the_dispatch_job_records_an_error_on_failure(): void
    {
        Http::fake(['voice.test/*' => Http::response(['error' => ['code' => 'x', 'message' => 'nope']], 500)]);

        $customer = $this->customer();
        $campaign = Campaign::create(['name' => 'Test', 'status' => 'draft', 'total_contacts' => 1]);

        (new DispatchCampaignJob($campaign->id, [$customer->id]))->handle(
            app(\App\Services\SarvamVoiceService::class)
        );

        $campaign->refresh();

        $this->assertSame('failed', $campaign->status);
        $this->assertNotNull($campaign->error_message);
        $this->assertStringNotContainsString('test-key-not-real', $campaign->error_message);
    }
}
