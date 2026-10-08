<?php

namespace App\Services;

use App\Models\Callback;
use App\Models\CallAttempt;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\UsageRecord;
use App\Support\CallStatus;
use Illuminate\Support\Facades\DB;

/**
 * Every number the dashboard shows comes from here, and every one of them is
 * a real database aggregate. Nothing is seeded, estimated or hardcoded.
 */
class DashboardMetrics
{
    /** @return array<string,mixed> */
    public function all(): array
    {
        return [
            'kpis'      => $this->kpis(),
            'campaign'  => $this->activeCampaignProgress(),
            'outcomes'  => $this->outcomeBreakdown(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /** @return array<string,mixed> */
    public function kpis(): array
    {
        $today = CallAttempt::query()->whereDate('created_at', today());

        $row = (clone $today)->selectRaw(<<<'SQL'
            COUNT(*) AS calls_today,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS connected,
            SUM(CASE WHEN connectivity_status = 'no_answer' THEN 1 ELSE 0 END) AS no_answer,
            SUM(CASE WHEN call_disposition = 'interested' THEN 1 ELSE 0 END) AS interested,
            SUM(CASE WHEN lead_generated = 1 OR call_disposition = 'interested' THEN 1 ELSE 0 END) AS hot_leads,
            SUM(CASE WHEN call_disposition = 'callback' THEN 1 ELSE 0 END) AS follow_ups,
            SUM(CASE WHEN duration_seconds IS NOT NULL THEN duration_seconds ELSE 0 END) AS talk_time,
            SUM(CASE WHEN duration_seconds IS NOT NULL THEN 1 ELSE 0 END) AS with_duration
        SQL)->first();

        $callsToday   = (int) ($row->calls_today ?? 0);
        $talkTime     = (int) ($row->talk_time ?? 0);
        $withDuration = (int) ($row->with_duration ?? 0);

        $callbacks = Customer::whereNotNull('next_callback_at')
            ->where('next_callback_at', '>=', now())
            ->count();

        return [
            'calls_today'     => $callsToday,
            'hot_leads'       => (int) ($row->hot_leads ?? 0),
            'follow_ups'      => (int) ($row->follow_ups ?? 0),
            'total_minutes'   => (int) round($talkTime / 60),
            'completed'       => (int) ($row->completed ?? 0),
            'connected'       => (int) ($row->connected ?? 0),
            'no_answer'       => (int) ($row->no_answer ?? 0),
            'interested'      => (int) ($row->interested ?? 0),
            'callbacks'       => $callbacks,
            'talk_time'       => $talkTime,
            'talk_time_human' => $this->humanDuration($talkTime),
            'avg_duration'    => $withDuration > 0 ? (int) round($talkTime / $withDuration) : null,
            'avg_duration_human' => $withDuration > 0
                ? $this->humanDuration((int) round($talkTime / $withDuration))
                : null,
        ];
    }

    /**
     * Progress for the most recent campaign that is actually running.
     * Returns null when there is none -- the view then says so rather than
     * inventing a progress bar.
     */
    public function activeCampaignProgress(): ?array
    {
        $campaign = Campaign::query()
            ->whereIn('status', ['active', 'dispatching', 'scheduled', 'paused'])
            ->whereNotNull('sarvam_campaign_id')
            ->latest('id')
            ->first()
            ?? Campaign::query()->whereNotNull('sarvam_campaign_id')->latest('id')->first();

        if (! $campaign) {
            return null;
        }

        $stats = CallAttempt::where('campaign_id', $campaign->sarvam_campaign_id)
            ->selectRaw(<<<'SQL'
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS connected,
                SUM(CASE WHEN connectivity_status = 'no_answer' THEN 1 ELSE 0 END) AS no_answer,
                SUM(CASE WHEN connectivity_status = 'busy' THEN 1 ELSE 0 END) AS busy,
                SUM(CASE WHEN connectivity_status = 'failed' THEN 1 ELSE 0 END) AS failed
            SQL)
            ->first();

        // Prefer the planned contact count; fall back to rows we actually have.
        $target    = max((int) $campaign->total_contacts, (int) ($stats->total ?? 0));
        $completed = (int) ($stats->completed ?? 0);

        return [
            'id'         => $campaign->id,
            'name'       => $campaign->name,
            'status'     => $campaign->status,
            'target'     => $target,
            'completed'  => $completed,
            'remaining'  => max(0, $target - $completed),
            'percent'    => $target > 0 ? round($completed / $target * 100, 1) : 0.0,
            'connected'  => (int) ($stats->connected ?? 0),
            'no_answer'  => (int) ($stats->no_answer ?? 0),
            'busy'       => (int) ($stats->busy ?? 0),
            'failed'     => (int) ($stats->failed ?? 0),
        ];
    }

    /**
     * Business outcomes across all completed calls.
     *
     * @return array<string,array{label:string,count:int}>
     */
    public function outcomeBreakdown(): array
    {
        $counts = CallAttempt::whereNotNull('call_disposition')
            ->groupBy('call_disposition')
            ->pluck(DB::raw('COUNT(*)'), 'call_disposition')
            ->all();

        $out = [];

        foreach (CallStatus::outcomeLabels() as $key => $label) {
            if ($key === CallStatus::UNKNOWN) {
                continue;
            }

            $out[$key] = ['label' => $label, 'count' => (int) ($counts[$key] ?? 0)];
        }

        return $out;
    }

    /** Most recent attempts, for the dashboard table. */
    public function recentCalls(int $limit = 8)
    {
        return CallAttempt::with('customer')->latest('id')->limit($limit)->get();
    }

    /** Customers with a callback scheduled from now onwards. */
    public function upcomingCallbacks(int $limit = 6)
    {
        return Customer::whereNotNull('next_callback_at')
            ->where('next_callback_at', '>=', now())
            ->orderBy('next_callback_at')
            ->limit($limit)
            ->get();
    }

    /** Is there campaign work in flight worth polling for? */
    public function hasLiveActivity(): bool
    {
        return Campaign::whereIn('status', ['dispatching', 'active'])->exists()
            || CallAttempt::whereIn('status', [CallStatus::QUEUED, CallStatus::DISPATCHED])->exists();
    }

    /**
     * Calls whose agent output marks them as a lead -- the Overview "Hot Leads"
     * rail and the Leads page both read from here, so the definition lives in
     * exactly one place (App\Support\LeadOutcome::isHot()).
     */
    public function hotLeads(int $limit = 5)
    {
        return $this->hotLeadQuery()->limit($limit)->get();
    }

    /** The same query, unbounded, for the paginated Leads page. */
    public function hotLeadQuery()
    {
        return CallAttempt::with('customer')
            ->where(function ($q) {
                $q->where('lead_generated', true)
                  ->orWhere('call_disposition', CallStatus::INTERESTED);
            })
            ->latest('id');
    }

    /**
     * The four numbers the simplified "Calls Overview" block shows, each with
     * its share of all calls. All real aggregates; zero when there is nothing.
     *
     * @return array{total:int,rows:array<int,array{label:string,count:int,percent:float,tone:string}>}
     */
    public function callsOverview(): array
    {
        $row = CallAttempt::query()->selectRaw(<<<'SQL'
            COUNT(*) AS total,
            SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS connected,
            SUM(CASE WHEN lead_generated = 1 OR call_disposition = 'interested' THEN 1 ELSE 0 END) AS interested,
            SUM(CASE WHEN call_disposition = 'callback' THEN 1 ELSE 0 END) AS follow_up,
            SUM(CASE WHEN connectivity_status = 'no_answer' THEN 1 ELSE 0 END) AS no_answer
        SQL)->first();

        $total = (int) ($row->total ?? 0);

        $rows = [];

        foreach ([
            ['Connected',  'connected',  'green'],
            ['Interested', 'interested', 'purple'],
            ['Follow-up',  'follow_up',  'cyan'],
            ['No Answer',  'no_answer',  'orange'],
        ] as [$label, $key, $tone]) {
            $count = (int) ($row->{$key} ?? 0);

            $rows[] = [
                'label'   => $label,
                'count'   => $count,
                'percent' => $total > 0 ? round($count / $total * 100, 1) : 0.0,
                'tone'    => $tone,
            ];
        }

        return ['total' => $total, 'rows' => $rows];
    }

    // =================================================================
    // Range-aware totals for the dashboard, analytics and usage pages
    // =================================================================

    /**
     * Headline totals since $since (all time when null).
     *
     * "Failed" means the call never connected because the line failed or we
     * could not dispatch it -- no-answer and busy are counted separately.
     *
     * @return array<string,mixed>
     */
    /**
     * The headline figures on the client dashboard.
     *
     * Every number is an aggregate of calls and results we actually hold. Where a
     * rate cannot be computed -- no calls yet, so no denominator -- it comes back
     * null and the view prints a dash, rather than a misleading zero per cent.
     *
     * @return array<string,mixed>
     */
    public function clientHeadline(): array
    {
        $today = $this->totals(today());
        $month = $this->totals(now()->startOfMonth());

        $usage = UsageRecord::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('COUNT(*) AS calls, COALESCE(SUM(billable_minutes), 0) AS minutes')
            ->first();

        return [
            'calls_today'          => $today['total_calls'],
            'calls_month'          => $month['total_calls'],
            'answered'             => $month['connected'],
            'no_answer'            => $month['no_answer'],
            'failed'               => $month['failed'],
            'interested'           => $month['leads'],
            'not_interested'       => $month['not_interested'],
            'avg_duration_human'   => $month['avg_duration_human'],
            // Interested out of answered: a call nobody picked up cannot convert.
            'conversion_rate'      => $month['lead_rate'],
            'callbacks_due'        => Callback::dueNow()->count(),
            'callbacks_upcoming'   => Callback::pending()->where('scheduled_at', '>', now())->count(),
            'usage_calls_month'    => (int) ($usage->calls ?? 0),
            'usage_minutes_month'  => (float) ($usage->minutes ?? 0),
        ];
    }

    public function totals(?\DateTimeInterface $since = null): array
    {
        $calls = CallAttempt::query();

        if ($since) {
            $calls->where('created_at', '>=', $since);
        }

        $row = $calls->selectRaw(<<<'SQL'
            COUNT(*) AS total,
            SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS connected,
            SUM(CASE WHEN connectivity_status = 'no_answer' THEN 1 ELSE 0 END) AS no_answer,
            SUM(CASE WHEN connectivity_status = 'busy' THEN 1 ELSE 0 END) AS busy,
            SUM(CASE WHEN connectivity_status = 'failed' OR status = 'failed' THEN 1 ELSE 0 END) AS failed,
            SUM(CASE WHEN status IN ('queued', 'dispatched') THEN 1 ELSE 0 END) AS in_flight,
            SUM(CASE WHEN lead_generated = 1 OR call_disposition = 'interested' THEN 1 ELSE 0 END) AS leads,
            SUM(CASE WHEN lead_generated = 1 THEN 1 ELSE 0 END) AS qualified,
            SUM(CASE WHEN call_disposition = 'not_interested' THEN 1 ELSE 0 END) AS not_interested,
            SUM(CASE WHEN call_disposition = 'callback' THEN 1 ELSE 0 END) AS callbacks_requested,
            SUM(COALESCE(duration_seconds, 0)) AS talk_time,
            SUM(CASE WHEN duration_seconds IS NOT NULL THEN 1 ELSE 0 END) AS with_duration
        SQL)->first();

        $total        = (int) ($row->total ?? 0);
        $connected    = (int) ($row->connected ?? 0);
        $talkTime     = (int) ($row->talk_time ?? 0);
        $withDuration = (int) ($row->with_duration ?? 0);
        $avg          = $withDuration > 0 ? (int) round($talkTime / $withDuration) : null;

        return [
            'total_calls'         => $total,
            'connected'           => $connected,
            'no_answer'           => (int) ($row->no_answer ?? 0),
            'busy'                => (int) ($row->busy ?? 0),
            'failed'              => (int) ($row->failed ?? 0),
            'in_flight'           => (int) ($row->in_flight ?? 0),
            'leads'               => (int) ($row->leads ?? 0),
            'qualified'           => (int) ($row->qualified ?? 0),
            'not_interested'      => (int) ($row->not_interested ?? 0),
            'callbacks_requested' => (int) ($row->callbacks_requested ?? 0),
            'connect_rate'        => $total > 0 ? round($connected / $total * 100, 1) : null,
            'lead_rate'           => $connected > 0 ? round((int) ($row->leads ?? 0) / $connected * 100, 1) : null,
            'talk_time'           => $talkTime,
            'talk_time_human'     => $this->humanDuration($talkTime),
            'minutes'             => (int) ceil($talkTime / 60),
            'avg_duration'        => $avg,
            'avg_duration_human'  => $avg !== null ? $this->humanDuration($avg) : null,
        ];
    }

    /** Customer-side counts (not range-bound: the CRM is a current snapshot). */
    public function customerTotals(): array
    {
        return [
            'customers'         => Customer::count(),
            'never_called'      => Customer::whereNull('last_call_at')->count(),
            'do_not_call'       => Customer::where('do_not_call', true)->count(),
            'pending_callbacks' => Customer::whereNotNull('next_callback_at')->where('next_callback_at', '>=', now())->count(),
            'overdue_callbacks' => Customer::whereNotNull('next_callback_at')->where('next_callback_at', '<', now())->count(),
        ];
    }

    /**
     * One row per day for the last $days days, zero-filled so a chart shows
     * quiet days honestly instead of skipping them.
     *
     * @return array{labels:list<string>,total:list<int>,connected:list<int>,failed:list<int>,has_data:bool}
     */
    public function callsOverTime(int $days = 14): array
    {
        $start = now()->startOfDay()->subDays($days - 1);

        $rows = CallAttempt::query()
            ->where('created_at', '>=', $start)
            ->selectRaw(<<<'SQL'
                DATE(created_at) AS day,
                COUNT(*) AS total,
                SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS connected,
                SUM(CASE WHEN connectivity_status IN ('failed', 'no_answer', 'busy') OR status = 'failed' THEN 1 ELSE 0 END) AS failed
            SQL)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy(fn ($r) => substr((string) $r->day, 0, 10));

        $out = ['labels' => [], 'total' => [], 'connected' => [], 'failed' => [], 'has_data' => false];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $row = $rows->get($day->toDateString());

            $out['labels'][]    = $day->format('d M');
            $out['total'][]     = (int) ($row->total ?? 0);
            $out['connected'][] = (int) ($row->connected ?? 0);
            $out['failed'][]    = (int) ($row->failed ?? 0);
        }

        $out['has_data'] = array_sum($out['total']) > 0;

        return $out;
    }

    /**
     * Outcomes since $since, only those that actually occurred.
     *
     * @return array<string,array{label:string,count:int}>
     */
    public function outcomesSince(?\DateTimeInterface $since = null): array
    {
        $q = CallAttempt::whereNotNull('call_disposition');

        if ($since) {
            $q->where('created_at', '>=', $since);
        }

        $counts = $q->groupBy('call_disposition')
            ->pluck(DB::raw('COUNT(*)'), 'call_disposition')
            ->all();

        $out = [];

        foreach (\App\Support\LeadOutcome::labels() as $key => $label) {
            if (($counts[$key] ?? 0) > 0) {
                $out[$key] = ['label' => $label, 'count' => (int) $counts[$key]];
            }
        }

        return $out;
    }

    /**
     * Per-campaign results for campaigns that reached the voice platform.
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    public function campaignPerformance(int $limit = 8)
    {
        $campaigns = Campaign::whereNotNull('sarvam_campaign_id')->latest('id')->limit($limit)->get();

        if ($campaigns->isEmpty()) {
            return collect();
        }

        $stats = CallAttempt::whereIn('campaign_id', $campaigns->pluck('sarvam_campaign_id'))
            ->selectRaw(<<<'SQL'
                campaign_id,
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS connected,
                SUM(CASE WHEN connectivity_status IN ('failed', 'no_answer', 'busy') THEN 1 ELSE 0 END) AS unreached,
                SUM(CASE WHEN lead_generated = 1 OR call_disposition = 'interested' THEN 1 ELSE 0 END) AS leads
            SQL)
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        return $campaigns->map(function (Campaign $c) use ($stats) {
            $s      = $stats->get($c->sarvam_campaign_id);
            $target = max((int) $c->total_contacts, (int) ($s->total ?? 0));
            $done   = (int) ($s->completed ?? 0);

            return [
                'id'        => $c->id,
                'name'      => $c->name,
                'status'    => $c->status,
                'target'    => $target,
                'completed' => $done,
                'connected' => (int) ($s->connected ?? 0),
                'unreached' => (int) ($s->unreached ?? 0),
                'leads'     => (int) ($s->leads ?? 0),
                'percent'   => $target > 0 ? round($done / $target * 100, 1) : 0.0,
            ];
        });
    }

    /**
     * Calls per agent since $since. Calls placed before agents existed (or
     * with none selected) are grouped under the workspace agent.
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    public function agentPerformance(?\DateTimeInterface $since = null)
    {
        $q = CallAttempt::query();

        if ($since) {
            $q->where('created_at', '>=', $since);
        }

        $rows = $q->selectRaw(<<<'SQL'
                agent_id,
                COUNT(*) AS total,
                SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS connected,
                SUM(CASE WHEN lead_generated = 1 OR call_disposition = 'interested' THEN 1 ELSE 0 END) AS leads,
                SUM(COALESCE(duration_seconds, 0)) AS talk_time
            SQL)
            ->groupBy('agent_id')
            ->get();

        $names = \App\Models\Agent::whereIn('id', $rows->pluck('agent_id')->filter())->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'name'      => $r->agent_id ? ($names[$r->agent_id] ?? 'Deleted agent') : 'Workspace agent',
            'total'     => (int) $r->total,
            'connected' => (int) $r->connected,
            'leads'     => (int) $r->leads,
            'minutes'   => (int) ceil(((int) $r->talk_time) / 60),
        ])->sortByDesc('total')->values();
    }

    private function humanDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0m';
        }

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return "{$h}h {$m}m";
        }

        return $m > 0 ? "{$m}m {$s}s" : "{$s}s";
    }
}
