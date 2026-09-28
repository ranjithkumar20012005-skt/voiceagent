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
