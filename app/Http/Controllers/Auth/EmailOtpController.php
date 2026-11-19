<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpThrottledException;
use App\Http\Controllers\Controller;
use App\Http\Responses\RoleHomeResponse;
use App\Services\Otp\OtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Email verification with a 6-digit code instead of a link.
 */
class EmailOtpController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RoleHomeResponse::homeFor($user));
        }

        return Inertia::render('auth/verify-otp', [
            'email' => $this->otp->mask($user->email),
            'cooldown' => $this->otp->cooldownRemaining($user->email, OtpPurpose::EmailVerification),
            'hasActiveCode' => $this->otp->active($user->email, OtpPurpose::EmailVerification) !== null,
            'expiresInMinutes' => $this->otp->expiryMinutes(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RoleHomeResponse::homeFor($user));
        }

        try {
            $code = $this->otp->issue($user->email, OtpPurpose::EmailVerification, ip: $request->ip());
        } catch (OtpThrottledException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        if (! $this->otp->deliver($user->email, OtpPurpose::EmailVerification, $code)) {
            return back()->withErrors(['code' => 'We could not send the email right now. Please try again in a few minutes.']);
        }

        return back()->with('status', 'A new code is on its way.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\s*\d{3}\s?\d{3}\s*$/'],
        ], [
            'code.regex' => 'Enter the 6-digit code from your email.',
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RoleHomeResponse::homeFor($user));
        }

        $result = $this->otp->verify($user->email, OtpPurpose::EmailVerification, $validated['code']);

        if (! $result->verified()) {
            return back()->withErrors(['code' => $result->status->message()]);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect()->intended(RoleHomeResponse::homeFor($user));
    }
}
