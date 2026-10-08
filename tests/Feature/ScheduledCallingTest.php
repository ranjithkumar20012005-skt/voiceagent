<?php

namespace Tests\Feature;

use App\Jobs\DispatchCampaignJob;
use App\Models\Agent;
use App\Models\Automation;
use App\Models\Campaign;
use App\Models\Customer;
use App\Support\CallStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Scheduled calling: wake at a set time, work the list, follow up.
 *
 * The three things worth guarding are that a schedule runs once a day rather
 * than on every scheduler tick, that follow-up mode only re-calls people who were
 * never actually reached, and that one client's schedule never selects another
 * client's customers.
 */
class ScheduledCallingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:\App\Models\Workspace,1:Agent} */
    private function liveAgent(string $business = 'ABC Hospital', string $email = 'a@example.test'): array
    {
        [$workspace] = $this->makeClient($business, $email);

        $agent = $this->withinWorkspace($workspace, function () {
            $agent = Agent::create([
                'name'         => 'Maya',
                'status'       => Agent::ACTIVE,
                'calling_mode' => Agent::MODE_BULK,
            ]);

            $agent->forceFill(['provider_agent_id' => 'abc-001', 'provider_agent_version' => 1])->save();

            return $agent;
        });

        return [$workspace, $agent];
    }

    private function schedule(Agent $agent, array $overrides = []): Automation
    {
        return $this->withinWorkspace($agent->workspace, function () use ($agent, $overrides) {
            $schedule = new Automation(array_merge([
                'agent_id'     => $agent->id,
                'name'         => 'Daily run',
                'enabled'      => true,
                'frequency'    => 'daily',
                'run_at'       => '09:00',
                'timezone'     => 'Asia/Kolkata',
                'window_start' => '09:00',
                'window_end'   => '20:00',
                'max_calls_per_run' => 100,
                'max_retries'  => 2,
                'min_days_between_calls' => 0,
                'expiry_within_days' => 0,
                'skip_already_renewed' => false,
                'skip_do_not_call' => true,
                'skip_active_callback' => true,
            ], $overrides));

            $schedule->workspace_id = $agent->workspace_id;
            $schedule->save();

            return $schedule;
        });
    }

    private function customer(Agent $agent, array $overrides = []): Customer
    {
        return $this->withinWorkspace($agent->workspace, fn () => Customer::create(array_merge([
            'name'         => 'Ravi Kumar',
            'phone_number' => '+9190000' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
        ], $overrides)));
    }

    // =================================================================
    // Due-ness
    // =================================================================

    public function test_a_schedule_is_due_after_its_time_and_not_before(): void
    {
        [, $agent] = $this->liveAgent();
        $schedule  = $this->schedule($agent, ['run_at' => '09:00']);

        $this->assertFalse($schedule->isDue(Carbon::parse('2026-10-07 08:30', 'Asia/Kolkata')));
        $this->assertTrue($schedule->isDue(Carbon::parse('2026-10-07 09:30', 'Asia/Kolkata')));
    }

    public function test_a_schedule_runs_once_a_day_not_on_every_tick(): void
    {
        [, $agent] = $this->liveAgent();
        $schedule  = $this->schedule($agent);

        $at = Carbon::parse('2026-10-07 10:00', 'Asia/Kolkata');

        $this->assertTrue($schedule->isDue($at));

        // After a run it is no longer due today, however often the scheduler
        // ticks -- otherwise a minutely cron would dial the list all day.
        $schedule->forceFill(['last_run_at' => $at])->save();

        $this->assertFalse($schedule->fresh()->isDue($at->copy()->addMinutes(5)));
        $this->assertFalse($schedule->fresh()->isDue($at->copy()->addHours(3)));

        // Tomorrow it is due again.
        $this->assertTrue($schedule->fresh()->isDue($at->copy()->addDay()));
    }

    public function test_a_schedule_outside_its_window_is_not_due(): void
    {
        [, $agent] = $this->liveAgent();
        $schedule  = $this->schedule($agent, ['window_start' => '09:00', 'window_end' => '10:00']);

        $this->assertFalse($schedule->isDue(Carbon::parse('2026-10-07 18:00', 'Asia/Kolkata')));
    }

    public function test_a_schedule_only_runs_on_its_chosen_days(): void
    {
        [, $agent] = $this->liveAgent();
        $schedule  = $this->schedule($agent, ['run_days' => ['Monday', 'Wednesday']]);

        // 7 Oct 2026 is a Wednesday, 8 Oct a Thursday.
        $this->assertTrue($schedule->isDue(Carbon::parse('2026-10-07 10:00', 'Asia/Kolkata')));
        $this->assertFalse($schedule->isDue(Carbon::parse('2026-10-08 10:00', 'Asia/Kolkata')));
    }

    public function test_a_disabled_schedule_is_never_due(): void
    {
        [, $agent] = $this->liveAgent();
        $schedule  = $this->schedule($agent, ['enabled' => false]);

        $this->assertFalse($schedule->isDue(Carbon::parse('2026-10-07 10:00', 'Asia/Kolkata')));
    }

    // =================================================================
    // Who it selects
    // =================================================================

    public function test_follow_up_mode_only_recalls_people_who_were_not_reached(): void
    {
        [, $agent] = $this->liveAgent();
        $schedule  = $this->schedule($agent, ['only_unreached' => true, 'max_retries' => 2]);

        $this->customer($agent, ['name' => 'Never tried', 'last_connectivity' => null]);
        $this->customer($agent, ['name' => 'No answer', 'last_connectivity' => CallStatus::NO_ANSWER, 'call_count' => 1]);
        $this->customer($agent, ['name' => 'Was busy', 'last_connectivity' => CallStatus::BUSY, 'call_count' => 1]);
        // Reached: left alone, whatever they said.
        $this->customer($agent, ['name' => 'Answered', 'last_connectivity' => CallStatus::CONNECTED, 'call_count' => 1]);

        $names = $this->withinWorkspace($agent->workspace, fn () => $schedule->eligibleCustomers()->pluck('name')->all());

        $this->assertContains('Never tried', $names);
        $this->assertContains('No answer', $names);
        $this->assertContains('Was busy', $names);
        $this->assertNotContains('Answered', $names);
    }

    public function test_follow_ups_stop_after_the_retry_limit(): void
    {
        [, $agent] = $this->liveAgent();
        $schedule  = $this->schedule($agent, ['only_unreached' => true, 'max_retries' => 2]);

        $this->customer($agent, ['name' => 'Tried twice', 'last_connectivity' => CallStatus::NO_ANSWER, 'call_count' => 2]);
        $this->customer($agent, ['name' => 'Tried five times', 'last_connectivity' => CallStatus::NO_ANSWER, 'call_count' => 5]);

        $names = $this->withinWorkspace($agent->workspace, fn () => $schedule->eligibleCustomers()->pluck('name')->all());

        $this->assertContains('Tried twice', $names);
        // An unreachable number is not dialled forever.
        $this->assertNotContains('Tried five times', $names);
    }

    public function test_a_schedule_never_calls_someone_who_asked_not_to_be(): void
    {
        [, $agent] = $this->liveAgent();
        $schedule  = $this->schedule($agent);

        $this->customer($agent, ['name' => 'Opted out', 'do_not_call' => true]);
        $this->customer($agent, ['name' => 'Fine to call']);

        $names = $this->withinWorkspace($agent->workspace, fn () => $schedule->eligibleCustomers()->pluck('name')->all());

        $this->assertNotContains('Opted out', $names);
        $this->assertContains('Fine to call', $names);
    }

    public function test_a_schedule_tied_to_a_list_ignores_everyone_else(): void
    {
        [, $agent] = $this->liveAgent();

        $batch = $this->withinWorkspace($agent->workspace, fn () => \App\Models\ImportBatch::create([
            'original_filename' => 'october.csv',
            'status'            => \App\Models\ImportBatch::COMPLETED,
            'valid_rows'        => 1,
        ]));

        $schedule = $this->schedule($agent, ['import_batch_id' => $batch->id]);

        $this->customer($agent, ['name' => 'On the list', 'import_batch_id' => $batch->id]);
        $this->customer($agent, ['name' => 'Not on the list']);

        $names = $this->withinWorkspace($agent->workspace, fn () => $schedule->eligibleCustomers()->pluck('name')->all());

        $this->assertSame(['On the list'], $names);
    }

    // =================================================================
    // Isolation
    // =================================================================

    public function test_a_schedule_never_selects_another_clients_customers(): void
    {
        [, $agentA] = $this->liveAgent('ABC Hospital', 'a@example.test');
        [, $agentB] = $this->liveAgent('XYZ Realty', 'b@example.test');

        $scheduleA = $this->schedule($agentA);

        $this->customer($agentA, ['name' => 'Belongs to A']);
        $this->customer($agentB, ['name' => 'Belongs to B']);

        $names = $this->withinWorkspace($agentA->workspace, fn () => $scheduleA->eligibleCustomers()->pluck('name')->all());

        $this->assertSame(['Belongs to A'], $names);
    }

    public function test_the_dispatcher_runs_each_schedule_as_its_own_client(): void
    {
        Queue::fake();

        [$a, $agentA] = $this->liveAgent('ABC Hospital', 'a@example.test');
        [$b, $agentB] = $this->liveAgent('XYZ Realty', 'b@example.test');

        // Both due, each with one person to call.
        $this->schedule($agentA, ['name' => 'A run', 'run_at' => '00:01', 'window_start' => '00:00', 'window_end' => '23:59']);
        $this->schedule($agentB, ['name' => 'B run', 'run_at' => '00:01', 'window_start' => '00:00', 'window_end' => '23:59']);

        $this->customer($agentA, ['name' => 'A person']);
        $this->customer($agentB, ['name' => 'B person']);

        $this->artisan('calls:dispatch-due')->assertSuccessful();

        // One campaign each, in the right workspace -- not one campaign holding
        // both clients' people.
        $this->assertSame(1, Campaign::withoutGlobalScope('workspace')->where('workspace_id', $a->id)->count());
        $this->assertSame(1, Campaign::withoutGlobalScope('workspace')->where('workspace_id', $b->id)->count());

        foreach (Campaign::withoutGlobalScope('workspace')->get() as $campaign) {
            $this->assertSame(1, $campaign->total_contacts);
        }

        Queue::assertPushed(DispatchCampaignJob::class, 2);
    }

    public function test_a_schedule_with_no_workspace_is_skipped_rather_than_run_globally(): void
    {
        Queue::fake();

        [, $agent] = $this->liveAgent();
        $this->customer($agent);

        // An orphaned schedule must not be allowed to select across every client.
        $orphan = $this->schedule($agent, ['name' => 'Orphan', 'run_at' => '00:01', 'window_start' => '00:00', 'window_end' => '23:59']);
        $orphan->forceFill(['workspace_id' => null, 'agent_id' => null])->save();

        $this->artisan('calls:dispatch-due')->assertSuccessful();

        $this->assertSame(0, Campaign::withoutGlobalScope('workspace')->count());
        Queue::assertNothingPushed();
    }
}
