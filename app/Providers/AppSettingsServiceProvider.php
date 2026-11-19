<?php

namespace App\Providers;

use App\Services\AppSettings;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AppSettings::class);
    }

    /**
     * Apply database settings on top of .env/config for this request or worker.
     */
    public function boot(): void
    {
        $settings = $this->app->make(AppSettings::class);
        $settings->applyToConfig();

        // Long running queue workers boot once, so pick up newer settings before every job.
        Event::listen(JobProcessing::class, function () use ($settings): void {
            $settings->refresh();
        });
    }
}
