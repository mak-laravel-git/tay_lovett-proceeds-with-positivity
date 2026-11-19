<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Enums\OtpPurpose;
use App\Enums\OtpStatus;
use App\Exceptions\OtpThrottledException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AppSettings;
use App\Services\Otp\OtpService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Forgot password with an emailed code.
 *
 * Every response looks the same whether or not the address belongs to an account, and the throttles are keyed on
 * the address only, so none of them can be used to discover who is registered. A correct code does not set a
 * password. It opens a short, single-use reset session bound to this browser.
 */
class PasswordResetOtpController extends Controller
{
    private const EMAIL_KEY = 'password_reset.email';

    private const GRANT_KEY = 'password_reset.grant';

    private const GRANT_MINUTES = 15;

    public function __construct(private OtpService $otp, private AppSettings $settings) {}

    public function request(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);
        $email = $this->otp->normalize($validated['email']);

        $this->issueIfAllowed($email, $request->ip());
        $request->session()->put(self::EMAIL_KEY, $email);
        RateLimiter::clear($this->failureKey($email));

        return redirect()->route('password.code')->with('status', 'If an account exists for that email, a code is on its way.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::EMAIL_KEY);

        if (! is_string($email)) {
            return redirect()->route('password.request');
        }

        $this->issueIfAllowed($email, $request->ip());
        RateLimiter::clear($this->failureKey($email));

        return back()->with('status', 'If an account exists for that email, a new code is on its way.');
    }

    public function showCode(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get(self::EMAIL_KEY);

        if (! is_string($email)) {
            return redirect()->route('password.request');
        }

        return Inertia::render('auth/verify-reset-code', [
            'email' => $this->otp->mask($email),
            'cooldown' => RateLimiter::availableIn($this->cooldownKey($email)),
            'expiresInMinutes' => $this->otp->expiryMinutes(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\s*\d{3}\s?\d{3}\s*$/'],
        ], [
            'code.regex' => 'Enter the 6-digit code from your email.',
        ]);

        $email = $request->session()->get(self::EMAIL_KEY);

        if (! is_string($email)) {
            return redirect()->route('password.request');
        }

        $failures = $this->failureKey($email);
        $max = (int) $this->settings->get('authentication.otp_max_attempts');

        if (RateLimiter::tooManyAttempts($failures, $max)) {
            return back()->withErrors(['code' => OtpStatus::TooManyAttempts->message()]);
        }

        $result = $this->otp->verify($email, OtpPurpose::PasswordReset, $validated['code']);

        if (! $result->verified()) {
            RateLimiter::hit($failures, $this->otp->expiryMinutes() * 60);

            // Missing, wrong and expired codes read the same so the answer never reveals whether an account exists.
            $message = in_array($result->status, [OtpStatus::Missing, OtpStatus::Invalid, OtpStatus::Expired], true)
                ? 'That code is not correct or has expired.'
                : $result->status->message();

            return back()->withErrors(['code' => $message]);
        }

        RateLimiter::clear($failures);
        $request->session()->put(self::GRANT_KEY, [
            'email' => $email,
            'expires_at' => now()->addMinutes(self::GRANT_MINUTES)->getTimestamp(),
        ]);
        $request->session()->regenerate();

        return redirect()->route('password.reset');
    }

    public function showReset(Request $request): Response|RedirectResponse
    {
        if ($this->grantedEmail($request) === null) {
            return redirect()->route('password.request');
        }

        return Inertia::render('auth/reset-password', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function update(Request $request, ResetUserPassword $reset): RedirectResponse
    {
        $email = $this->grantedEmail($request);

        if ($email === null) {
            return redirect()->route('password.request')->withErrors(['email' => 'Your reset session expired. Start again.']);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null || $user->isRestricted()) {
            $request->session()->forget([self::EMAIL_KEY, self::GRANT_KEY]);

            return redirect()->route('password.request')->withErrors(['email' => 'This password cannot be reset. Contact support.']);
        }

        $reset->reset($user, $request->only('password', 'password_confirmation'));

        $user->forceFill(['remember_token' => Str::random(60)])->save();

        // Sign the account out everywhere, so anyone who had the old password loses access.
        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        $this->otp->forget($email, OtpPurpose::PasswordReset);
        $request->session()->forget([self::EMAIL_KEY, self::GRANT_KEY]);
        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Your password was changed. Log in with the new one.');
    }

    /**
     * Send a code only when the address is not throttled and belongs to an active account. The throttle itself
     * ticks for unknown addresses too, which keeps every outcome identical from the outside.
     */
    private function issueIfAllowed(string $email, ?string $ip): void
    {
        $cooldown = $this->cooldownKey($email);
        $hourly = 'forgot-hour:'.sha1($email);
        $maxPerHour = (int) $this->settings->get('authentication.otp_max_sends_per_hour');

        if (RateLimiter::tooManyAttempts($cooldown, 1) || RateLimiter::tooManyAttempts($hourly, $maxPerHour)) {
            return;
        }

        RateLimiter::hit($cooldown, (int) $this->settings->get('authentication.otp_resend_cooldown_seconds'));
        RateLimiter::hit($hourly, 3600);

        $user = User::query()->where('email', $email)->first();

        if ($user === null || $user->isRestricted()) {
            return;
        }

        try {
            $code = $this->otp->issue($email, OtpPurpose::PasswordReset, ip: $ip);
        } catch (OtpThrottledException) {
            return;
        }

        // Send after the response so the time taken does not depend on whether the account exists.
        // The guard keeps the callback from running again if the process handles more than one request.
        $sent = false;

        app()->terminating(function () use (&$sent, $email, $code): void {
            if (! $sent) {
                $sent = true;
                $this->otp->deliver($email, OtpPurpose::PasswordReset, $code);
            }
        });
    }

    private function grantedEmail(Request $request): ?string
    {
        $grant = $request->session()->get(self::GRANT_KEY);

        if (! is_array($grant) || ($grant['expires_at'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        return is_string($grant['email'] ?? null) ? $grant['email'] : null;
    }

    private function cooldownKey(string $email): string
    {
        return 'forgot-cooldown:'.sha1($email);
    }

    private function failureKey(string $email): string
    {
        return 'forgot-fail:'.sha1($email);
    }
}
