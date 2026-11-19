<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMaintenanceMode();
    }

    /**
     * Administrators and the authentication pages stay reachable while the site is in maintenance mode, so an
     * administrator never has to know the bypass secret just to sign in and turn maintenance mode back off.
     * Everything else still requires either the secret URL or its bypass cookie, exactly like a normal
     * `php artisan down`.
     */
    protected function configureMaintenanceMode(): void
    {
        PreventRequestsDuringMaintenance::except([
            'admin', 'admin/*',
            'login', 'logout',
            'two-factor-challenge', 'confirm-access/*', 'user/confirm-password',
            'up',
            // Provider confirmations are retried for a limited time; a missed one would lose a reward.
            'api/webhooks/*',
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Social crawlers need absolute https share/OG URLs when the site is served over https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
