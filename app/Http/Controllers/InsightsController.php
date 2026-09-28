<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\CallAttempt;
use App\Services\DashboardMetrics;
use App\Services\SarvamVoiceService;
use Illuminate\Http\Request;
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
        ]);
    }

    public function usage(Request $request): View
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();

        $current  = $this->metrics->totals($thisMonth);
        $previous = CallAttempt::whereBetween('created_at', [$lastMonth, $thisMonth])
            ->selectRaw('COUNT(*) AS total, SUM(COALESCE(duration_seconds, 0)) AS talk_time')
            ->first();

        return view('insights.usage', [
            'current'  => $current,
            'previous' => [
                'total_calls' => (int) ($previous->total ?? 0),
                'minutes'     => (int) ceil(((int) ($previous->talk_time ?? 0)) / 60),
            ],
            'month'    => now()->format('F Y'),
            'agents'   => $this->metrics->agentPerformance($thisMonth),
            'series'   => $this->metrics->callsOverTime(now()->day),
            'allTime'  => $this->metrics->totals(),
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

    public function phoneNumbers(): View
    {
        $status = $this->voice->publicStatus();

        return view('insights.phone-numbers', [
            'status' => $status,
            'agents' => Agent::active()->orderByDesc('is_default')->orderBy('name')->get(),
            'calls'  => $status['caller_number']
                ? CallAttempt::where('agent_phone_number', $status['caller_number'])->count()
                : 0,
            'lastCall' => $status['caller_number']
                ? CallAttempt::where('agent_phone_number', $status['caller_number'])->latest('id')->value('created_at')
                : null,
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
