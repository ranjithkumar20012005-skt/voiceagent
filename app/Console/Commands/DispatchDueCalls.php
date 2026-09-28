<?php

namespace App\Console\Commands;

use App\Jobs\DispatchCampaignJob;
use App\Models\Automation;
use App\Models\Campaign;
use App\Services\SarvamVoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Runs the enabled automations. Scheduled daily; also safe to run by hand.
 *
 *   php artisan calls:dispatch-due
 *   php artisan calls:dispatch-due --dry-run
 */
class DispatchDueCalls extends Command
{
    protected $signature = 'calls:dispatch-due
                            {--automation= : Run only this automation ID}
                            {--dry-run : Report what would be called without dispatching}
                            {--force : Ignore the time-of-day window check}';

    protected $description = 'Queue outbound calls for customers matching each enabled automation';

    public function handle(SarvamVoiceService $sarvam): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $sarvam->isConfigured()) {
            $this->error('Voice service is not configured. Missing: ' . implode(', ', $sarvam->missingConfigKeys()));

            return self::FAILURE;
        }

        $query = Automation::query()->where('enabled', true);

        if ($id = $this->option('automation')) {
            $query = Automation::query()->whereKey($id);
        }

        $automations = $query->get();

        if ($automations->isEmpty()) {
            $this->info('No enabled automations to run.');

            return self::SUCCESS;
        }

        foreach ($automations as $automation) {
            $this->runAutomation($automation, $dryRun);
        }

        return self::SUCCESS;
    }

    private function runAutomation(Automation $automation, bool $dryRun): void
    {
        $this->line("Automation: {$automation->name}");

        if (! $this->option('force') && ! $automation->withinWindow()) {
            $this->warn("  Skipped -- outside the calling window ({$automation->window_start}-{$automation->window_end} {$automation->timezone}).");

            return;
        }

        $limit = max(1, min($automation->max_calls_per_run, (int) config('sarvam.campaign.max_calls_per_run', 200)));

        $customers = $automation->eligibleCustomers()->limit($limit)->get();

        $this->line("  Eligible customers: {$customers->count()} (cap {$limit})");

        if ($customers->isEmpty()) {
            $automation->update([
                'last_run_at'      => now(),
                'last_run_status'  => 'no_targets',
                'last_run_message' => 'No customers matched the filters.',
                'last_run_count'   => 0,
            ]);

            return;
        }

        if ($dryRun) {
            $this->table(
                ['ID', 'Name', 'Phone', 'Policy expiry'],
                $customers->take(20)->map(fn ($c) => [
                    $c->id, $c->name, $c->phone_number, optional($c->policy_expiry_date)->toDateString(),
                ])->all(),
            );
            $this->info('  Dry run -- nothing dispatched.');

            return;
        }

        $campaign = Campaign::create([
            'name'                => sprintf('%s %s', $automation->name, now($automation->timezone)->format('d M Y')),
            'description'         => 'Created automatically by the ' . $automation->name . ' automation.',
            'status'              => 'draft',
            'automation_id'       => $automation->id,
            'total_contacts'      => $customers->count(),
            'attempts_per_second' => $automation->attempts_per_second,
            'starts_at'           => now(),
            'ends_at'             => now()->addDay(),
        ]);

        DispatchCampaignJob::dispatch($campaign->id, $customers->pluck('id')->all());

        $automation->update([
            'last_run_at'      => now(),
            'last_run_status'  => 'dispatched',
            'last_run_message' => "Queued {$customers->count()} calls as campaign #{$campaign->id}.",
            'last_run_count'   => $customers->count(),
        ]);

        Log::info('automation.dispatched', [
            'automation_id' => $automation->id,
            'campaign_id'   => $campaign->id,
            'count'         => $customers->count(),
        ]);

        $this->info("  Queued {$customers->count()} calls as campaign #{$campaign->id}.");
    }
}
