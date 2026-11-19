<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\RoleHomeResponse;
use App\Http\Responses\RolePasskeyLoginResponse;
use App\Models\User;
use App\Services\AppSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, RoleHomeResponse::class);
        $this->app->singleton(TwoFactorLoginResponse::class, RoleHomeResponse::class);
        $this->app->singleton(PasskeyLoginResponse::class, RolePasskeyLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        // A passkey is a full sign-in, so banned and suspended accounts must not get through it either.
        Passkeys::authorizeLoginUsing(fn (Request $request, ?User $user): bool => $user !== null && ! $user->isRestricted());

        Fortify::authenticateUsing(function (Request $request): ?User {
            /** @var User|null $user */
            $user = User::query()->where('email', $request->input(Fortify::username()))->first();

            if ($user === null || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            if ($user->isBanned()) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('This account has been banned.'),
                ]);
            }

            if ($user->isSuspended()) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('This account is suspended until :until.', [
                        'until' => $user->suspended_until?->toDayDateTimeString() ?? 'further notice',
                    ]),
                ]);
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => true,
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'registrationOpen' => (bool) app(AppSettings::class)->get('authentication.registration_open'),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn (Request $request) => Inertia::render('auth/confirm-password', [
            'hasPasskeys' => $request->user()?->hasPasskeysEnabled() ?? false,
            'hasTwoFactor' => $request->user()?->hasEnabledTwoFactorAuthentication() ?? false,
        ]));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('otp-send', function (Request $request) {
            return Limit::perMinute(6)->by(($request->user()?->id ?? Str::lower((string) $request->input('email'))).'|'.$request->ip());
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinute(10)->by(($request->user()?->id ?? $request->session()->getId()).'|'.$request->ip());
        });

        RateLimiter::for('confirm-access', function (Request $request) {
            return Limit::perMinute(5)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
