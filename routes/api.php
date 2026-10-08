<?php

use App\Http\Controllers\SarvamWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Stateless, no session, no CSRF -- and deliberately tiny. The only endpoint
| here is the voice platform's completion callback, which authenticates with a
| secret path token and is rate limited.
|
| Web forms keep full CSRF protection; nothing about this file weakens that.
|
*/

Route::post('/webhooks/sarvam/{token}', [SarvamWebhookController::class, 'handle'])
    ->middleware('throttle:webhook')
    ->where('token', '[A-Za-z0-9_\-]{16,128}')
    ->name('webhooks.sarvam');

// ---------------------------------------------------------------
// Instant lead intake
// ---------------------------------------------------------------
// Public by necessity: a website form or an automation tool has no session. The
// per-source token in the path is the credential, and the controller compares it
// in constant time. Rate limited because it is reachable from anywhere.
Route::post('/leads/{token}', [App\Http\Controllers\LeadIntakeController::class, 'store'])
    ->middleware('throttle:120,1')
    ->name('leads.intake');

// Lead-ads providers verify a subscription with a GET challenge first.
Route::get('/leads/{token}', [App\Http\Controllers\LeadIntakeController::class, 'verify'])
    ->middleware('throttle:60,1')
    ->name('leads.verify');
