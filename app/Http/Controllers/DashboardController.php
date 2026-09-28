<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetrics;
use App\Services\SarvamVoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardMetrics $metrics,
        private readonly SarvamVoiceService $sarvam,
    ) {
    }

    public function index(): View
    {
        return view('dashboard.index', [
            'kpis'       => $this->metrics->kpis(),
            'hotLeads'   => $this->metrics->hotLeads(),
            'overview'   => $this->metrics->callsOverview(),
            'recent'     => $this->metrics->recentCalls(),
            'live'       => $this->metrics->hasLiveActivity(),
            'configured' => $this->sarvam->isConfigured(),
            'totals'     => $this->metrics->totals(),
            'customers'  => $this->metrics->customerTotals(),
            'series'     => $this->metrics->callsOverTime(14),
            'outcomes'   => $this->metrics->outcomesSince(),
            'campaign'   => $this->metrics->activeCampaignProgress(),
            'callbacks'  => $this->metrics->upcomingCallbacks(5),
        ]);
    }

    /**
     * Lightweight polling endpoint for the live tiles.
     *
     * Returns aggregates only -- no credentials, no vendor identifiers.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'kpis'     => $this->metrics->kpis(),
            'overview' => $this->metrics->callsOverview(),
            'live'     => $this->metrics->hasLiveActivity(),
            'recent'   => $this->metrics->recentCalls()->map(fn ($a) => [
                'id'           => $a->id,
                'customer'     => $a->customer?->name ?: 'Unknown',
                'phone'        => $a->display_phone,
                'connectivity' => $a->connectivity_status,
                'connectivity_label' => $a->connectivity_label,
                'outcome'      => $a->call_disposition,
                'outcome_label' => $a->outcome_label,
                'duration'     => $a->duration_for_humans,
                'created_at'   => $a->created_at?->diffForHumans(),
            ])->values(),
        ]);
    }
}
