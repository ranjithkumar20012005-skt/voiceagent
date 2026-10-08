<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\CallAttempt;
use App\Models\Callback;
use App\Models\PhoneNumber;
use App\Models\UsageRecord;
use App\Services\DashboardMetrics;
use App\Services\SarvamVoiceService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Read-only pages built entirely from data the application already holds:
 * Analytics, Usage, Providers and Phone Numbers. Nothing here calls the voice
 * platform, and no credential or secret value is ever passed to a view.
 */
class InsightsController extends Controller
{
    /** Range key => [label, days (null = all time)]. */
    private const RANGES = [
        '7d'  => ['7 days', 7],
        '30d' => ['30 days', 30],
        '90d' => ['90 days', 90],
        'all' => ['All time', null],
    ];

    public function __construct(
        private readonly DashboardMetrics $metrics,
        private readonly SarvamVoiceService $voice,
    ) {
    }

    public function analytics(Request $request): View
    {
        [$range, $since, $days] = $this->range($request, '30d');

        return view('insights.analytics', [
            'range'     => $range,
            'ranges'    => self::RANGES,
            'totals'    => $this->metrics->totals($since),
            'series'    => $this->metrics->callsOverTime(min($days ?? 90, 90)),
            'outcomes'  => $this->metrics->outcomesSince($since),
            'campaigns' => $this->metrics->campaignPerformance(),
            'agents'    => $this->metrics->agentPerformance($since),
            // Answered vs not, day by day.
            'answerTrend' => $this->answerTrend($since),
            // By language. Returns empty when no call has recorded a language,
            // and the view then omits the section entirely rather than inventing
            // a breakdown out of nulls.
            'byLanguage'  => $this->byLanguage($since),
            'callbacks'   => Callback::query()
                ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
                ->count(),
        ]);
    }

    /**
     * What this client has used.
     *
     * Reads `usage_records`, which the result processor writes one row per call
     * to. Call counts came from call_attempts before, which could not express
     * billable minutes and had no per-client cost at all.
     *
     * `provider_cost` is never selected here: it is our wholesale figure and is
     * hidden on the model as well, so it cannot reach the view by any route.
     */
    public function usage(Request $request): View
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();

        return view('insights.usage', [
            'current'  => $this->usageTotals($thisMonth),
            'previous' => $this->usageTotals($lastMonth, $thisMonth),
            'allTime'  => $this->usageTotals(),
            'month'    => now()->format('F Y'),
            'daily'    => $this->usageByDay(),
            'perAgent' => $this->usageByAgent($thisMonth),
            // Kept so the page can still show calling activity alongside usage.
            'calls'    => $this->metrics->totals($thisMonth),
            'series'   => $this->metrics->callsOverTime(max(1, now()->day)),
        ]);
    }

    /**
     * Answered against unanswered, per day.
     *
     * @return \Illuminate\Support\Collection<int,array{day:string,answered:int,unanswered:int}>
     */
    private function answerTrend(?\DateTimeInterface $since): \Illuminate\Support\Collection
    {
        return CallAttempt::query()
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->selectRaw(
                "DATE(created_at) AS day,
                 SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS answered,
                 SUM(CASE WHEN connectivity_status IS NOT NULL AND connectivity_status <> 'connected' THEN 1 ELSE 0 END) AS unanswered"
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day'        => Carbon::parse($row->day)->format('j M'),
                'answered'   => (int) $row->answered,
                'unanswered' => (int) $row->unanswered,
            ]);
    }

    /**
     * Calls grouped by the language the conversation actually ran in.
     *
     * Only rows that recorded one are counted. An empty result means we hold no
     * language data, not that every call was in one language.
     *
     * @return \Illuminate\Support\Collection<int,array{language:string,calls:int,answered:int,interested:int}>
     */
    private function byLanguage(?\DateTimeInterface $since): \Illuminate\Support\Collection
    {
        return CallAttempt::query()
            ->whereNotNull('language')
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->selectRaw(
                "language,
                 COUNT(*) AS calls,
                 SUM(CASE WHEN connectivity_status = 'connected' THEN 1 ELSE 0 END) AS answered,
                 SUM(CASE WHEN lead_generated = 1 OR call_disposition = 'interested' THEN 1 ELSE 0 END) AS interested"
            )
            ->groupBy('language')
            ->orderByDesc('calls')
            ->get()
            ->map(fn ($row) => [
                'language'   => (string) $row->language,
                'calls'      => (int) $row->calls,
                'answered'   => (int) $row->answered,
                'interested' => (int) $row->interested,
            ]);
    }

    /** @return array{calls:int,minutes:float,seconds:int,cost:float,currency:string} */
    private function usageTotals(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $query = UsageRecord::query();

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<', $to);
        }

        $row = $query->selectRaw(
            'COUNT(*) AS calls,
             COALESCE(SUM(billable_minutes), 0) AS minutes,
             COALESCE(SUM(duration_seconds), 0) AS seconds,
             COALESCE(SUM(customer_cost), 0) AS cost'
        )->first();

        return [
            'calls'    => (int) ($row->calls ?? 0),
            'minutes'  => round((float) ($row->minutes ?? 0), 2),
            'seconds'  => (int) ($row->seconds ?? 0),
            'cost'     => round((float) ($row->cost ?? 0), 2),
            'currency' => (string) config('voice.billing.currency', 'INR'),
        ];
    }

    /** Daily usage across the current month, for the trend. */
    private function usageByDay(): \Illuminate\Support\Collection
    {
        return UsageRecord::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS calls, COALESCE(SUM(billable_minutes), 0) AS minutes')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day'     => Carbon::parse($row->day)->format('j M'),
                'calls'   => (int) $row->calls,
                'minutes' => round((float) $row->minutes, 2),
            ]);
    }

    /** Per-agent usage, shown only when the workspace has more than one agent. */
    private function usageByAgent(\DateTimeInterface $from): \Illuminate\Support\Collection
    {
        return UsageRecord::query()
            ->where('created_at', '>=', $from)
            ->whereNotNull('agent_id')
            ->selectRaw('agent_id, COUNT(*) AS calls, COALESCE(SUM(billable_minutes), 0) AS minutes')
            ->groupBy('agent_id')
            ->orderByDesc('minutes')
            ->get()
            ->map(fn ($row) => [
                'agent'   => Agent::find($row->agent_id)?->name ?? 'Unknown agent',
                'calls'   => (int) $row->calls,
                'minutes' => round((float) $row->minutes, 2),
            ]);
    }

    /**
     * Provider status. "Connected" is only claimed when the application has
     * actually completed a request (a call was accepted); an upstream error on
     * the most recent attempt shows as "Error". Otherwise it is honest about
     * being merely configured.
     */
    public function providers(): View
    {
        $status = $this->voice->publicStatus();

        $lastAccepted = CallAttempt::whereNotNull('attempt_id')->latest('id')->first();
        $lastFailed   = CallAttempt::where('status', 'failed')->whereNull('attempt_id')->latest('id')->first();
        $lastWebhook  = CallAttempt::whereNotNull('webhook_received_at')->latest('webhook_received_at')->first();

        if (! $status['configured']) {
            $state = 'not_configured';
        } elseif ($lastFailed && (! $lastAccepted || $lastFailed->id > $lastAccepted->id)) {
            $state = 'error';
        } elseif ($lastAccepted) {
            $state = 'connected';
        } else {
            $state = 'configured';
        }

        return view('insights.providers', [
            'status'       => $status,
            'state'        => $state,
            'missing'      => $this->voice->missingConfigKeys(),
            'lastAccepted' => $lastAccepted?->created_at,
            'lastFailedAt' => $lastFailed?->created_at,
            'lastWebhook'  => $lastWebhook?->webhook_received_at,
        ]);
    }

    /**
     * The numbers allocated to this workspace from our pool.
     *
     * Reads our own records rather than the server environment, so a client sees
     * their own lines and nothing about the platform behind them.
     */
    public function phoneNumbers(): View
    {
        $workspace = app(Tenancy::class)->workspace();

        $numbers = $workspace
            ? PhoneNumber::with('agent')->ownedBy($workspace)->orderBy('phone_number')->get()
            : collect();

        $callCounts = CallAttempt::query()
            ->whereIn('agent_phone_number', $numbers->pluck('phone_number'))
            ->selectRaw('agent_phone_number, COUNT(*) AS total')
            ->groupBy('agent_phone_number')
            ->pluck('total', 'agent_phone_number');

        $lastUsed = CallAttempt::query()
            ->whereIn('agent_phone_number', $numbers->pluck('phone_number'))
            ->selectRaw('agent_phone_number, MAX(created_at) AS used_at')
            ->groupBy('agent_phone_number')
            ->pluck('used_at', 'agent_phone_number')
            ->map(fn ($at) => Carbon::parse($at)->diffForHumans());

        return view('insights.phone-numbers', [
            'numbers'    => $numbers,
            'callCounts' => $callCounts,
            'lastUsed'   => $lastUsed,
            'hasInbound' => Agent::where('calling_mode', Agent::MODE_INBOUND)->exists(),
        ]);
    }

    /** Knowledge Base and Tools are shown honestly as not connected. */
    public function knowledge(): View
    {
        return view('insights.unavailable', ['feature' => 'knowledge']);
    }

    public function tools(): View
    {
        return view('insights.unavailable', ['feature' => 'tools']);
    }

    /** @return array{0:string,1:?\Illuminate\Support\Carbon,2:?int} */
    private function range(Request $request, string $default): array
    {
        $key  = array_key_exists($request->query('range'), self::RANGES) ? $request->query('range') : $default;
        $days = self::RANGES[$key][1];

        return [$key, $days ? now()->startOfDay()->subDays($days - 1) : null, $days];
    }
}
