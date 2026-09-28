<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
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
            'settings' => [
                'default_language'    => AppSetting::get('default_language', config('sarvam.default_language')),
                'retry_limit'         => AppSetting::get('retry_limit', config('sarvam.campaign.retry.max_retries')),
                'automation_time'     => AppSetting::get('automation_time', '08:00'),
                'max_calls_per_run'   => AppSetting::get('max_calls_per_run', config('sarvam.campaign.max_calls_per_run')),
                'window_start'        => AppSetting::get('window_start', config('sarvam.campaign.window.start')),
                'window_end'          => AppSetting::get('window_end', config('sarvam.campaign.window.end')),
            ],
            'languages' => config('sarvam.languages', []),
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

        AppSetting::putMany($data);

        return back()->with('status', 'Settings saved.');
    }
}
