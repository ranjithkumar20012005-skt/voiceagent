<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\CallbackController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\CallingController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SettingsController;
use App\Http\Middleware\DemoReadOnly;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------
// Public marketing site
// ---------------------------------------------------------------
Route::get('/', [PageController::class, 'home'])->name('home');

// ---------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
// "Start Free": straight to the dashboard with a read-only demo session
// when DEMO_START_FREE_ENABLED is on; otherwise the sign-in page.
Route::get('/start', [AuthController::class, 'startFree'])
    ->middleware('throttle:30,1')
    ->name('start');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('login.submit');

// ---------------------------------------------------------------
// Authenticated application
// ---------------------------------------------------------------
Route::middleware(['auth', DemoReadOnly::class])->group(function () {
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
    Route::post('/callbacks/{customer}/clear', [CallbackController::class, 'clear'])->name('callbacks.clear');

    // Automations
    Route::get('/automations', [AutomationController::class, 'index'])->name('automations.index');
    Route::post('/automations', [AutomationController::class, 'store'])->name('automations.store');
    Route::put('/automations/{automation}', [AutomationController::class, 'update'])->name('automations.update');
    Route::post('/automations/{automation}/run', [AutomationController::class, 'run'])->name('automations.run');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Agents -- pointers to agents that live in the voice platform.
    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/create', [AgentController::class, 'create'])->name('agents.create');
    Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
    Route::get('/agents/{agent}/edit', [AgentController::class, 'edit'])->name('agents.edit');
    Route::put('/agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
    Route::post('/agents/{agent}/default', [AgentController::class, 'makeDefault'])->name('agents.default');
    Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');

    // Insights and deployment -- read-only, built from data we already hold.
    Route::get('/analytics', [InsightsController::class, 'analytics'])->name('analytics.index');
    Route::get('/usage', [InsightsController::class, 'usage'])->name('usage.index');
    Route::get('/providers', [InsightsController::class, 'providers'])->name('providers.index');
    Route::get('/phone-numbers', [InsightsController::class, 'phoneNumbers'])->name('phone-numbers.index');
    Route::get('/knowledge-base', [InsightsController::class, 'knowledge'])->name('knowledge.index');
    Route::get('/tools', [InsightsController::class, 'tools'])->name('tools.index');
});
