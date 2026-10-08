<?php

namespace Tests\Feature;

use App\Jobs\PlaceInstantCallJob;
use App\Models\Agent;
use App\Models\AgentLeadSource;
use App\Models\Customer;
use App\Services\CallingSchedule;
use App\Services\SarvamException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Instant leads: a lead arrives from somewhere public and gets called.
 *
 * What matters here is that the intake is safe to expose (token only, no
 * session), that it never dials inside the request, and that a lead with no
 * usable number is rejected rather than stored as a customer nobody can ring.
 */
class InstantLeadsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:\App\Models\Workspace,1:Agent,2:AgentLeadSource} */
    private function liveAgentWithSource(array $sourceAttributes = []): array
    {
        [$workspace, $user] = $this->makeClient('ABC Hospital', 'a@example.test');

        [$agent, $source] = $this->withinWorkspace($workspace, function () use ($sourceAttributes) {
            $agent = Agent::create([
                'name'         => 'Maya',
                'status'       => Agent::ACTIVE,
                'calling_mode' => Agent::MODE_INSTANT_LEADS,
                'is_always_on' => true,
            ]);

            $agent->forceFill(['provider_agent_id' => 'abc-001', 'provider_agent_version' => 1])->save();

            $source = new AgentLeadSource(array_merge([
                'agent_id' => $agent->id,
                'name'     => 'Website form',
                'kind'     => 'website_form',
                'enabled'  => true,
            ], $sourceAttributes));

            $source->workspace_id = $agent->workspace_id;
            $source->save();

            return [$agent, $source];
        });

        return [$workspace, $agent, $source];
    }

    private function url(AgentLeadSource $source): string
    {
        return '/api/leads/' . $source->token;
    }

    // =================================================================
    // Intake
    // =================================================================

    public function test_a_lead_is_captured_and_the_call_is_queued_not_dialled(): void
    {
        Queue::fake();
        [$workspace, $agent, $source] = $this->liveAgentWithSource();

        $this->postJson($this->url($source), [
            'name'  => 'Ravi Kumar',
            'phone' => '9876543210',
            'email' => 'ravi@example.test',
            'city'  => 'Coimbatore',
        ])->assertStatus(202)->assertJson(['accepted' => true, 'queued' => true]);

        $customer = Customer::withoutGlobalScope('workspace')->firstOrFail();

        $this->assertSame($workspace->id, $customer->workspace_id);
        $this->assertSame('Ravi Kumar', $customer->name);
        // Normalised to E.164 on the way in.
        $this->assertSame('+919876543210', $customer->phone_number);
        $this->assertSame('website_form', $customer->source);

        // The provider is never called inside the request.
        Queue::assertPushed(PlaceInstantCallJob::class, fn ($job) => $job->agentId === $agent->id
            && $job->customerId === $customer->id);
    }

    public function test_a_lead_with_no_usable_number_is_rejected_and_counted(): void
    {
        Queue::fake();
        [, , $source] = $this->liveAgentWithSource();

        $this->postJson($this->url($source), ['name' => 'No Number', 'phone' => 'not a phone'])
            ->assertStatus(202)
            ->assertJson(['accepted' => false]);

        // No customer nobody can call, and the rejection is visible to the client.
        $this->assertSame(0, Customer::withoutGlobalScope('workspace')->count());
        $this->assertSame(1, $source->fresh()->rejected_count);
        Queue::assertNothingPushed();
    }

    public function test_a_repeat_enquiry_updates_the_same_person(): void
    {
        Queue::fake();
        [, , $source] = $this->liveAgentWithSource();

        $this->postJson($this->url($source), ['phone' => '9876543210', 'name' => 'Ravi'])->assertStatus(202);
        $this->postJson($this->url($source), ['phone' => '+919876543210', 'name' => 'Ravi Kumar'])->assertStatus(202);

        $this->assertSame(1, Customer::withoutGlobalScope('workspace')->count());
        $this->assertSame('Ravi Kumar', Customer::withoutGlobalScope('workspace')->first()->name);
        $this->assertSame(2, $source->fresh()->lead_count);
    }

    public function test_a_lead_ads_style_payload_is_understood(): void
    {
        Queue::fake();
        [, , $source] = $this->liveAgentWithSource(['kind' => 'meta_lead']);

        // The name/values shape lead-ads providers post.
        $this->postJson($this->url($source), [
            'field_data' => [
                ['name' => 'full_name', 'values' => ['Sneha Rao']],
                ['name' => 'phone_number', 'values' => ['+91 98765 43211']],
                ['name' => 'email', 'values' => ['sneha@example.test']],
            ],
        ])->assertStatus(202)->assertJson(['accepted' => true]);

        $customer = Customer::withoutGlobalScope('workspace')->firstOrFail();

        $this->assertSame('Sneha Rao', $customer->name);
        $this->assertSame('+919876543211', $customer->phone_number);
    }

    public function test_an_explicit_field_map_wins_over_the_guessed_one(): void
    {
        Queue::fake();
        [, , $source] = $this->liveAgentWithSource([
            'field_map' => ['phone' => 'contact_no', 'name' => 'lead_full_name'],
        ]);

        $this->postJson($this->url($source), [
            'contact_no'     => '9876543212',
            'lead_full_name' => 'Imran Shaikh',
            // A decoy the guesser would otherwise have taken.
            'phone'          => '0000000000',
        ])->assertStatus(202);

        $customer = Customer::withoutGlobalScope('workspace')->firstOrFail();

        $this->assertSame('+919876543212', $customer->phone_number);
        $this->assertSame('Imran Shaikh', $customer->name);
    }

    // =================================================================
    // Access
    // =================================================================

    public function test_an_unknown_or_short_token_is_a_404(): void
    {
        Queue::fake();
        $this->liveAgentWithSource();

        $this->postJson('/api/leads/' . str_repeat('x', 48), ['phone' => '9876543210'])->assertNotFound();
        $this->postJson('/api/leads/short', ['phone' => '9876543210'])->assertNotFound();

        $this->assertSame(0, Customer::withoutGlobalScope('workspace')->count());
        Queue::assertNothingPushed();
    }

    public function test_a_paused_source_stops_accepting_leads(): void
    {
        Queue::fake();
        [, , $source] = $this->liveAgentWithSource();

        $source->forceFill(['enabled' => false])->save();

        $this->postJson($this->url($source), ['phone' => '9876543210'])->assertNotFound();
        Queue::assertNothingPushed();
    }

    public function test_an_empty_payload_is_refused(): void
    {
        Queue::fake();
        [, , $source] = $this->liveAgentWithSource();

        $this->postJson($this->url($source), [])->assertStatus(422);
    }

    public function test_the_token_is_never_serialized(): void
    {
        [, , $source] = $this->liveAgentWithSource();

        $this->assertArrayNotHasKey('token', $source->toArray());
    }

    public function test_leads_land_only_in_the_owning_workspace(): void
    {
        Queue::fake();
        [$a, , $sourceA] = $this->liveAgentWithSource();
        [$b, $clientB]   = $this->makeClient('XYZ Realty', 'b@example.test');

        $this->postJson($this->url($sourceA), ['phone' => '9876543210', 'name' => 'Ravi'])->assertStatus(202);

        // The other client sees nothing of it.
        $this->assertSame(0, $this->withinWorkspace($b, fn () => Customer::count()));
        $this->assertSame(1, $this->withinWorkspace($a, fn () => Customer::count()));
    }

    // =================================================================
    // Calling hours
    // =================================================================

    public function test_an_always_on_agent_is_open_at_any_hour(): void
    {
        [, $agent] = $this->liveAgentWithSource();

        $schedule = CallingSchedule::for($agent);

        $this->assertTrue($schedule->isOpen(Carbon::parse('2026-10-07 03:00:00')));
        $this->assertSame(0, $schedule->delaySeconds(Carbon::parse('2026-10-07 03:00:00')));
        $this->assertStringContainsString('24 hours', $schedule->describe());
    }

    public function test_a_windowed_agent_is_closed_outside_its_hours(): void
    {
        [$workspace, $agent] = $this->liveAgentWithSource();

        $agent->forceFill([
            'is_always_on'         => false,
            'calling_window_start' => '09:00',
            'calling_window_end'   => '20:00',
            'calling_days'         => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'timezone'             => 'Asia/Kolkata',
        ])->save();

        $schedule = CallingSchedule::for($agent->refresh());

        // Wednesday 7 Oct 2026, in the agent's own timezone.
        $this->assertTrue($schedule->isOpen(Carbon::parse('2026-10-07 11:00:00', 'Asia/Kolkata')));
        $this->assertFalse($schedule->isOpen(Carbon::parse('2026-10-07 03:00:00', 'Asia/Kolkata')));
        $this->assertFalse($schedule->isOpen(Carbon::parse('2026-10-07 22:00:00', 'Asia/Kolkata')));

        // A Sunday is not an allowed day.
        $this->assertFalse($schedule->isOpen(Carbon::parse('2026-10-11 11:00:00', 'Asia/Kolkata')));
    }

    public function test_a_lead_outside_hours_is_held_rather_than_dropped(): void
    {
        [, $agent] = $this->liveAgentWithSource();

        $agent->forceFill([
            'is_always_on'         => false,
            'calling_window_start' => '09:00',
            'calling_window_end'   => '20:00',
            'calling_days'         => ['Wednesday'],
            'timezone'             => 'Asia/Kolkata',
        ])->save();

        $schedule = CallingSchedule::for($agent->refresh());
        $at       = Carbon::parse('2026-10-07 03:00:00', 'Asia/Kolkata');

        // It waits until the window opens, and the wait is a real positive delay.
        $this->assertGreaterThan(0, $schedule->delaySeconds($at));
        $this->assertSame('09:00', $schedule->nextOpening($at)->format('H:i'));
    }

    public function test_the_schedule_uses_the_agents_timezone_not_the_servers(): void
    {
        [, $agent] = $this->liveAgentWithSource();

        $agent->forceFill([
            'is_always_on'         => false,
            'calling_window_start' => '09:00',
            'calling_window_end'   => '17:00',
            'calling_days'         => ['Wednesday'],
            'timezone'             => 'Asia/Kolkata',
        ])->save();

        $schedule = CallingSchedule::for($agent->refresh());

        // 05:00 UTC is 10:30 in Kolkata, which is inside the window. Comparing
        // in server time would have called this closed.
        $this->assertTrue($schedule->isOpen(Carbon::parse('2026-10-07 05:00:00', 'UTC')));
    }

    // =================================================================
    // Provider failures stay ours
    // =================================================================

    /**
     * Regression test for a real leak: the operator wording for 401/402/429
     * names the provider, our API key and our account balance, and it was being
     * returned straight to the client in the call response.
     */
    public function test_a_provider_failure_is_reworded_before_a_client_sees_it(): void
    {
        $failure = new SarvamException('raw upstream text', 402);

        // The operator wording does name them -- that is its job.
        $this->assertStringContainsString('Sarvam', $failure->userMessage());
        $this->assertStringContainsString('credits', $failure->userMessage());

        // What a client is shown names none of it.
        $client = $failure->clientMessage();

        foreach (['sarvam', 'credit', 'balance', 'api key', 'top up'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $client);
        }

        $this->assertSame('Calling could not be started.', $client);

        // A transient upstream fault reads as transient, so a client knows to retry.
        $this->assertSame(
            'Voice service is temporarily unavailable.',
            (new SarvamException('raw', 503))->clientMessage(),
        );

        // And an auth failure is not dressed up as the client's problem.
        $this->assertSame('Calling could not be started.', (new SarvamException('raw', 401))->clientMessage());
    }
}
