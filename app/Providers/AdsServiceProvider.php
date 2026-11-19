<?php

namespace App\Providers;

use App\Services\Ads\AdProviderRegistry;
use App\Services\Ads\Providers\GoogleAdSense\GoogleAdSenseAdapter;
use App\Services\Ads\Providers\TapResearch\TapResearchAdapter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Registers every ad / survey provider adapter. A new provider is one adapter class plus one line here.
 */
class AdsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdProviderRegistry::class, function ($app): AdProviderRegistry {
            $registry = new AdProviderRegistry;
            $registry->register($app->make(TapResearchAdapter::class));
            $registry->register($app->make(GoogleAdSenseAdapter::class));

            return $registry;
        });
    }

    public function boot(): void
    {
        // Providers retry failed callbacks and send bursts from a handful of IPs, so this is generous; the
        // signature check, not the limiter, is what protects the endpoint.
        RateLimiter::for('provider-callbacks', fn (Request $request): Limit => Limit::perMinute(600)->by((string) $request->ip()));
    }
}
