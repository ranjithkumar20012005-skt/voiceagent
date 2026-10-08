<?php

use App\Http\Controllers\AgentBuilderController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentSetupController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\CallbackController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\CallingController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Internal\AgentRequestController as InternalAgentRequestController;
use App\Http\Controllers\Internal\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------
// Public marketing site
// ---------------------------------------------------------------
Route::get('/', [PageController::class, 'home'])->name('home');

// ---------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
// "Start Free": straight to the dashboard (development demo session only).
Route::get('/start', [AuthController::class, 'startFree'])
    ->middleware('throttle:30,1')
    ->name('start');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('login.submit');

// Customer self-signup: creates the account and its workspace together.
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('register.submit');

// ---------------------------------------------------------------
// Authenticated application
// ---------------------------------------------------------------
// `workspace` resolves the tenant every query below is scoped by. It must stay
// paired with `auth`: without it the model scopes have no workspace to filter on.
Route::middleware(['auth', 'workspace'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Overview
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    // Calling -- instant call is live; bulk and inbound are shown as upcoming.
    Route::get('/calling', [CallingController::class, 'index'])->name('calling.index');

    // Leads
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/template', [CustomerController::class, 'template'])->name('customers.template');
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

    // Imports
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('/imports', [ImportController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('imports.store');
    Route::get('/imports/{batch}/map', [ImportController::class, 'map'])->name('imports.map');
    Route::post('/imports/{batch}/process', [ImportController::class, 'process'])->name('imports.process');
    Route::get('/imports/{batch}', [ImportController::class, 'show'])->name('imports.show');
    Route::get('/imports/{batch}/status', [ImportController::class, 'status'])->name('imports.status');
    Route::delete('/imports/{batch}', [ImportController::class, 'destroy'])->name('imports.destroy');

    // Calls
    Route::get('/calls', [CallController::class, 'index'])->name('calls.index');
    Route::post('/calls', [CallController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('calls.store');
    Route::get('/calls/{call}', [CallController::class, 'show'])->name('calls.show');

    // Campaigns
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::post('/campaigns', [CampaignController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('campaigns.store');
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::post('/campaigns/{campaign}/status', [CampaignController::class, 'updateStatus'])->name('campaigns.status');
    Route::get('/campaigns/{campaign}/refresh', [CampaignController::class, 'refresh'])->name('campaigns.refresh');

    // Callbacks
    Route::get('/callbacks', [CallbackController::class, 'index'])->name('callbacks.index');
    Route::post('/callbacks/{callback}/complete', [CallbackController::class, 'complete'])->name('callbacks.complete');
    Route::post('/callbacks/{callback}/cancel', [CallbackController::class, 'cancel'])->name('callbacks.cancel');
    // Clears the CRM reminder on a customer; kept for the Customers screens.
    Route::post('/callbacks/customer/{customer}/clear', [CallbackController::class, 'clear'])->name('callbacks.clear');

    // Automations
    Route::get('/automations', [AutomationController::class, 'index'])->name('automations.index');
    Route::post('/automations', [AutomationController::class, 'store'])->name('automations.store');
    Route::put('/automations/{automation}', [AutomationController::class, 'update'])->name('automations.update');
    Route::post('/automations/{automation}/run', [AutomationController::class, 'run'])->name('automations.run');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Agents. A client builds and runs their own.
    //
    // There used to be a second path here -- "Request from our team", a form
    // that filed a brief for staff to build instead. Taken back out of the
    // dashboard on request: one path, and creating an agent is the whole
    // action, not the start of a conversation. AgentBuildRequest and the
    // internal queue at internal.agent-requests.* still exist unlinked, in
    // case the client-facing side comes back; nothing reaches them from here.
    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');

    // Create / edit an agent from our own dashboard.
    //
    // Open to any signed-in workspace user: the form, the provisioning and the
    // tenant scoping are the same whether a client builds their own agent here
    // or our team builds it for them from the internal area.
    Route::get('/agents/create', [AgentBuilderController::class, 'create'])->name('agents.create');
    Route::post('/agents', [AgentBuilderController::class, 'store'])->name('agents.store');
    Route::get('/agents/{agent}/edit', [AgentBuilderController::class, 'edit'])->name('agents.edit');
    Route::put('/agents/{agent}', [AgentBuilderController::class, 'update'])->name('agents.update');
    Route::get('/agents/{agent}/prompt', [AgentBuilderController::class, 'preview'])->name('agents.preview');
    Route::post('/agents/{agent}/provision', [AgentBuilderController::class, 'provision'])
        ->middleware('throttle:10,1')
        ->name('agents.provision');

    // How the agent gets people to call, and when it may call them.
    Route::get('/agents/{agent}/setup', [AgentSetupController::class, 'show'])->name('agents.setup');
    Route::put('/agents/{agent}/setup', [AgentSetupController::class, 'update'])->name('agents.setup.update');

    // Instant leads: the connected sources.
    Route::post('/agents/{agent}/sources', [AgentSetupController::class, 'addSource'])->name('agents.sources.store');
    Route::post('/agents/{agent}/sources/{source}/toggle', [AgentSetupController::class, 'toggleSource'])->name('agents.sources.toggle');
    Route::delete('/agents/{agent}/sources/{source}', [AgentSetupController::class, 'removeSource'])->name('agents.sources.destroy');

    // Bulk: start the agent on a list.
    Route::post('/agents/{agent}/campaign', [AgentSetupController::class, 'startCampaign'])
        ->middleware('throttle:10,1')
        ->name('agents.campaign.start');

    // Scheduled calling: wake at a set time, work the list, follow up.
    Route::post('/agents/{agent}/schedule', [AgentSetupController::class, 'saveSchedule'])->name('agents.schedule.save');
    Route::delete('/agents/{agent}/schedule/{schedule}', [AgentSetupController::class, 'deleteSchedule'])->name('agents.schedule.destroy');
    Route::post('/agents/{agent}/schedule/{schedule}/run', [AgentSetupController::class, 'runSchedule'])
        ->middleware('throttle:10,1')
        ->name('agents.schedule.run');

    // Run / pause the agent itself.
    Route::post('/agents/{agent}/start', [AgentSetupController::class, 'start'])->name('agents.start');
    Route::post('/agents/{agent}/pause', [AgentSetupController::class, 'pause'])->name('agents.pause');

    // Polled from the Agents page while a card is still building or sitting
    // with the team -- see components/agent-card.blade.php.
    Route::get('/agents/{agent}/card', [AgentController::class, 'cardFragment'])->name('agents.card');

    // Declared after /agents/create so the literal path is matched first.
    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');

    // Conversations -- the same call records as Call Logs, filtered to the ones
    // that produced a transcript. No second copy of the transcript is stored.
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');

    // Insights and deployment -- read-only, built from data we already hold.
    Route::get('/analytics', [InsightsController::class, 'analytics'])->name('analytics.index');
    Route::get('/usage', [InsightsController::class, 'usage'])->name('usage.index');
    // Internal staff only: this page reports the voice platform's configuration
    // and connection state, which is ours. It 404s for a client.
    Route::get('/providers', [InsightsController::class, 'providers'])
        ->middleware('admin')
        ->name('providers.index');
    Route::get('/phone-numbers', [InsightsController::class, 'phoneNumbers'])->name('phone-numbers.index');
    Route::get('/knowledge-base', [InsightsController::class, 'knowledge'])->name('knowledge.index');
    Route::get('/tools', [InsightsController::class, 'tools'])->name('tools.index');
});

// ---------------------------------------------------------------
// Internal area -- our own team only
// ---------------------------------------------------------------
// Deliberately NOT inside the `workspace` group: an administrator works across
// tenants, so these routes resolve the workspace from the URL instead. The
// `admin` middleware is what closes the area -- it 404s for client users, so a
// client cannot even tell it exists.
Route::middleware(['auth', 'admin'])->prefix('internal')->name('internal.')->group(function () {
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{workspace}', [ClientController::class, 'show'])->name('clients.show');

    Route::post('/clients/{workspace}/agent', [ClientController::class, 'mapAgent'])->name('clients.agent.map');
    Route::post('/clients/{workspace}/agent/{agentId}/status', [ClientController::class, 'setAgentStatus'])->name('clients.agent.status');
    Route::post('/clients/{workspace}/users', [ClientController::class, 'addUser'])->name('clients.users.store');
    Route::post('/clients/{workspace}/status', [ClientController::class, 'setWorkspaceStatus'])->name('clients.status');

    Route::post('/numbers/import', [ClientController::class, 'importNumbers'])->name('numbers.import');
    Route::post('/clients/{workspace}/numbers', [ClientController::class, 'assignNumber'])->name('clients.numbers.assign');
    Route::delete('/clients/{workspace}/numbers/{numberId}', [ClientController::class, 'releaseNumber'])->name('clients.numbers.release');

    // "Build it for us" requests, across every client at once -- the queue staff
    // work from instead of waiting to be told a request landed on a client page.
    Route::get('/agent-requests', [InternalAgentRequestController::class, 'index'])->name('agent-requests.index');
    Route::put('/agent-requests/{agentBuildRequest}', [InternalAgentRequestController::class, 'update'])->name('agent-requests.update');
});
