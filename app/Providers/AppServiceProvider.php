<?php

namespace App\Providers;

use App\Contracts\HostedCallingProviderInterface;
use App\Contracts\PhoneNumberProviderInterface;
use App\Contracts\TestProviderInterface;
use App\Contracts\VoiceAgentProviderInterface;
use App\Models\Agent;
use App\Models\AgentBuildRequest;
use App\Models\CallAttempt;
use App\Models\Callback;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\ImportBatch;
use App\Models\PhoneNumber;
use App\Policies\AgentPolicy;
use App\Policies\CallAttemptPolicy;
use App\Policies\CallbackPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ImportBatchPolicy;
use App\Policies\PhoneNumberPolicy;
use App\Services\Provider\Sarvam\SarvamApiClient;
use App\Services\SarvamVoiceService;
use App\Support\Tenancy;
use InvalidArgumentException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One shared, configured client for the whole application.
        $this->app->singleton(SarvamVoiceService::class, fn () => SarvamVoiceService::make());

        // The workspace the current request or job is acting for. A singleton so
        // every model scope in the same request sees the same tenant.
        $this->app->singleton(Tenancy::class);

        $this->registerVoiceProviders();
    }

    /**
     * Bind the voice contracts to the configured provider's adapters.
     *
     * Controllers and jobs depend on the interfaces, so switching provider --
     * or moving from a hosted engine to a self-run pipeline -- is a change to
     * config/voice.php and a new set of adapters, not a rewrite.
     */
    private function registerVoiceProviders(): void
    {
        $this->app->singleton(SarvamApiClient::class, fn () => SarvamApiClient::make());

        $provider = (string) config('voice.provider', 'sarvam');
        $adapters = config("voice.providers.{$provider}");

        if (! is_array($adapters)) {
            throw new InvalidArgumentException("No voice adapters configured for provider [{$provider}].");
        }

        $contracts = [
            'agent'   => VoiceAgentProviderInterface::class,
            'calling' => HostedCallingProviderInterface::class,
            'numbers' => PhoneNumberProviderInterface::class,
            'tests'   => TestProviderInterface::class,
        ];

        foreach ($contracts as $key => $contract) {
            if (! empty($adapters[$key])) {
                $this->app->singleton($contract, $adapters[$key]);
            }
        }
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->registerPolicies();
        $this->shareLayoutData();

        Paginator::defaultView('components.pagination');
    }

    /**
     * Ownership checks for tenant-owned models. Registered explicitly rather
     * than relying on discovery so a renamed class fails loudly instead of
     * quietly leaving a model unprotected.
     */
    private function registerPolicies(): void
    {
        Gate::policy(Agent::class, AgentPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(ImportBatch::class, ImportBatchPolicy::class);
        Gate::policy(CallAttempt::class, CallAttemptPolicy::class);
        Gate::policy(Callback::class, CallbackPolicy::class);
        Gate::policy(PhoneNumber::class, PhoneNumberPolicy::class);
    }

    /**
     * Values the shared chrome (sidebar, header) needs on every authenticated
     * page. Resolved lazily so unauthenticated and console requests pay nothing.
     */
    private function shareLayoutData(): void
    {
        View::composer(['components.sidebar', 'components.header', 'components.new-call-modal', 'layouts.app', 'calling.index'], function ($view) {
            // These counts are per customer, so they must not be computed
            // without a workspace in context: with none set the model scopes
            // stop filtering and the sidebar would total every tenant's rows.
            $scoped = Auth::check() && app(Tenancy::class)->check();

            $view->with([
                'agentConfigured'  => app(SarvamVoiceService::class)->isConfigured(),
                'pendingCallbacks' => $scoped
                    ? Customer::whereNotNull('next_callback_at')->where('next_callback_at', '>=', now())->count()
                    : 0,
                'overdueCallbacks' => $scoped
                    ? Customer::whereNotNull('next_callback_at')->where('next_callback_at', '<', now())->count()
                    : 0,
                'agentLanguages'   => config('sarvam.languages', []),
                // Names only -- never the platform identifiers behind them.
                'callAgents'       => $scoped
                    ? Agent::active()->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'is_default', 'default_language'])
                    : collect(),
                // The Internal nav badge. Across every workspace on purpose -- an
                // admin may be viewing this composer from inside their own
                // workspace's scope, and the count must still be the global
                // total -- so the workspace scope is dropped explicitly. Cheap
                // to skip for a client, who never renders the Internal group.
                'pendingAgentRequests' => Auth::check() && Auth::user()?->is_internal_admin
                    ? AgentBuildRequest::withoutGlobalScope('workspace')
                        ->whereIn('status', [AgentBuildRequest::PENDING, AgentBuildRequest::IN_PROGRESS])
                        ->count()
                    : 0,
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
