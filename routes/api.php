<?php

use App\Http\Controllers\Api\ProviderCallbackController;
use App\Http\Controllers\Api\TheoremReachEventWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Server-to-server provider callbacks
|--------------------------------------------------------------------------
|
| Stateless: no session, no CSRF. Authenticity comes from each provider's signature, verified in the adapter.
| The URL a provider is configured with is /api/webhooks/{provider}/reward.
*/
Route::middleware('throttle:provider-callbacks')
    ->get('webhooks/{provider}/reward', ProviderCallbackController::class)
    ->where('provider', '[a-z0-9_-]+')
    ->name('webhooks.reward');

/*
| TheoremReach Event Webhook (reversals / bans). Separate from the reward callback per publisher FAQ.
| Accept GET or POST; signature is on the full URL (enc_salt + hash query params).
*/
Route::middleware('throttle:provider-callbacks')
    ->match(['get', 'post'], 'webhooks/theoremreach/events', TheoremReachEventWebhookController::class)
    ->name('webhooks.theoremreach.events');
