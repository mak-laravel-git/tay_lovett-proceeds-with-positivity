<?php

namespace App\Http\Controllers\Settings;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpThrottledException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Otp\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Change the sign-in email. The new address is only saved after its owner types the code we email there,
 * so a typo or a stolen session cannot point an account at an inbox nobody controls.
 */
class EmailChangeController extends Controller
{
    public const SESSION_KEY = 'email_change.pending';

    public function __construct(private OtpService $otp) {}

    public function request(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Compare addresses in lower case so Taken@Example.com cannot slip past the uniqueness check.
        $request->merge(['new_email' => $this->otp->normalize((string) $request->input('new_email'))]);

        $validated = $request->validate([
            'new_email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class, 'email')],
            'current_password' => ['required', 'string', 'current_password'],
        ], [
            'new_email.unique' => 'That email address is already in use.',
        ]);

        $email = $this->otp->normalize($validated['new_email']);

        if ($email === $this->otp->normalize($user->email)) {
            return back()->withErrors(['new_email' => 'That is already your email address.']);
        }

        return $this->send($request, $user, $email);
    }

    public function resend(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! is_string($pending)) {
            return back();
        }

        return $this->send($request, $request->user(), $pending);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\s*\d{3}\s?\d{3}\s*$/'],
        ], [
            'code.regex' => 'Enter the 6-digit code from your email.',
        ]);

        $user = $request->user();
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! is_string($pending)) {
            return back()->withErrors(['code' => 'Start the email change again.']);
        }

        $result = $this->otp->verify($pending, OtpPurpose::EmailChange, $validated['code']);

        if (! $result->verified()) {
            return back()->withErrors(['code' => $result->status->message()]);
        }

        // The code row is tied to the account that asked for it. A code someone else started never applies here.
        if (($result->meta['user_id'] ?? null) !== $user->id) {
            return back()->withErrors(['code' => 'That code is not valid for this account.']);
        }

        if (User::query()->where('email', $pending)->whereKeyNot($user->id)->exists()) {
            $request->session()->forget(self::SESSION_KEY);

            return back()->withErrors(['code' => 'That email address was taken in the meantime.']);
        }

        $user->forceFill(['email' => $pending, 'email_verified_at' => now()])->save();
        $request->session()->forget(self::SESSION_KEY);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Your email address was changed.']);

        return to_route('profile.edit');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $pending = $request->session()->pull(self::SESSION_KEY);

        if (is_string($pending)) {
            $this->otp->forget($pending, OtpPurpose::EmailChange);
        }

        return back();
    }

    private function send(Request $request, User $user, string $email): RedirectResponse
    {
        try {
            $code = $this->otp->issue($email, OtpPurpose::EmailChange, ['user_id' => $user->id], $request->ip());
        } catch (OtpThrottledException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->put(self::SESSION_KEY, $email);

        if (! $this->otp->deliver($email, OtpPurpose::EmailChange, $code)) {
            return back()->withErrors(['code' => 'We could not send the email right now. Please try again in a few minutes.']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'We sent a code to '.$this->otp->mask($email).'.']);

        return back();
    }
}
