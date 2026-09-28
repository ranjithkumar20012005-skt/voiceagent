<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\Customer;
use App\Services\SarvamVoiceService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One shared, configured client for the whole application.
        $this->app->singleton(SarvamVoiceService::class, fn () => SarvamVoiceService::make());
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->shareLayoutData();

        Paginator::defaultView('components.pagination');
    }

    /**
     * Values the shared chrome (sidebar, header) needs on every authenticated
     * page. Resolved lazily so unauthenticated and console requests pay nothing.
     */
    private function shareLayoutData(): void
    {
        View::composer(['components.sidebar', 'components.header', 'components.new-call-modal', 'layouts.app', 'calling.index'], function ($view) {
            $view->with([
                'agentConfigured'  => app(SarvamVoiceService::class)->isConfigured(),
                'pendingCallbacks' => Auth::check()
                    ? Customer::whereNotNull('next_callback_at')->where('next_callback_at', '>=', now())->count()
                    : 0,
                'overdueCallbacks' => Auth::check()
                    ? Customer::whereNotNull('next_callback_at')->where('next_callback_at', '<', now())->count()
                    : 0,
                'agentLanguages'   => config('sarvam.languages', []),
                // Names only -- never the platform identifiers behind them.
                'callAgents'       => Auth::check()
                    ? Agent::active()->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'is_default', 'default_language'])
                    : collect(),
            ]);
        });
    }

    private function configureRateLimiting(): void
    {
        // Completion callbacks. Generous enough for a campaign running at full
        // tilt, tight enough to blunt a flood at a public endpoint.
        RateLimiter::for('webhook', fn (Request $request) => [
            Limit::perMinute(600)->by($request->ip()),
        ]);
    }
}
