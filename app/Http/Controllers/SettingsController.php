<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\WorkspaceSetting;
use App\Services\SarvamVoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Read-only agent status plus the handful of safe, operator-tunable defaults.
 * Secrets are never displayed or editable here -- they live in the environment.
 */
class SettingsController extends Controller
{
    public function __construct(private readonly SarvamVoiceService $sarvam)
    {
    }

    public function index(): View
    {
        return view('settings.index', [
            'status'   => $this->sarvam->publicStatus(),
            'missing'  => $this->sarvam->missingConfigKeys(),
            // Per-workspace now: these were global, so one client changing a
            // calling default changed it for every other client too.
            'settings' => [
                'default_language'    => WorkspaceSetting::get('default_language', config('sarvam.default_language')),
                'retry_limit'         => WorkspaceSetting::get('retry_limit', config('sarvam.campaign.retry.max_retries')),
                'automation_time'     => WorkspaceSetting::get('automation_time', '08:00'),
                'max_calls_per_run'   => WorkspaceSetting::get('max_calls_per_run', config('sarvam.campaign.max_calls_per_run')),
                'window_start'        => WorkspaceSetting::get('window_start', config('sarvam.campaign.window.start')),
                'window_end'          => WorkspaceSetting::get('window_end', config('sarvam.campaign.window.end')),
            ],
            'languages' => config('sarvam.languages', []),
            // The client's own agent, in place of the platform identifiers that
            // used to be rendered here.
            'primaryAgent' => Agent::with('phoneNumber')->orderByDesc('is_default')->orderBy('name')->first(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'default_language'  => ['required', Rule::in(config('sarvam.languages', []))],
            'retry_limit'       => ['required', 'integer', 'min:0', 'max:20'],
            'automation_time'   => ['required', 'date_format:H:i'],
            'max_calls_per_run' => ['required', 'integer', 'min:1', 'max:10000'],
            'window_start'      => ['required', 'date_format:H:i'],
            'window_end'        => ['required', 'date_format:H:i', 'after:window_start'],
        ]);

        WorkspaceSetting::putMany($data);

        return back()->with('status', 'Settings saved.');
    }
}
