<?php

namespace App\Http\Controllers;

use App\Models\CallAttempt;
use App\Services\SarvamVoiceService;
use Illuminate\View\View;

/**
 * The "Calling" page.
 *
 * Presentation only -- placing a call still goes through CallController@store
 * and the existing voice service. Bulk calling and inbound are shown as
 * upcoming so the client sees the roadmap without half-built screens.
 */
class CallingController extends Controller
{
    public function __construct(private readonly SarvamVoiceService $voice)
    {
    }

    public function index(): View
    {
        return view('calling.index', [
            'configured' => $this->voice->isConfigured(),
            'languages'  => config('sarvam.languages', []),
            'recent'     => $this->recentInstantCalls(),
        ]);
    }

    /**
     * Calls placed one at a time from this app, i.e. everything that did not
     * come out of a campaign dispatch.
     */
    private function recentInstantCalls(int $limit = 8)
    {
        return CallAttempt::with(['customer', 'agent'])
            ->whereNull('campaign_id')
            ->where('direction', 'outbound')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
