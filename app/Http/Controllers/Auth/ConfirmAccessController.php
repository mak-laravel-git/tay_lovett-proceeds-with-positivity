<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\RoleHomeResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * Lets a user confirm it is really them with an authenticator code or a recovery code,
 * as an alternative to a password or passkey, before opening Security settings.
 */
class ConfirmAccessController extends Controller
{
    public function twoFactor(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return back()->withErrors(['code' => 'Two-factor authentication is not set up on this account.']);
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:20', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'max:40', 'required_without:code'],
        ]);

        $confirmed = false;

        if (filled($validated['code'] ?? null)) {
            $confirmed = $provider->verify(
                Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                preg_replace('/\s+/', '', $validated['code']),
            );
        } elseif (filled($validated['recovery_code'] ?? null)) {
            $matched = collect($user->recoveryCodes())
                ->first(fn (string $code): bool => hash_equals($code, trim($validated['recovery_code'])));

            if ($matched !== null) {
                $user->replaceRecoveryCode($matched);
                $confirmed = true;
            }
        }

        if (! $confirmed) {
            return back()->withErrors([
                filled($validated['code'] ?? null) ? 'code' : 'recovery_code' => 'That code is not valid.',
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', Date::now()->unix());

        return redirect()->intended(RoleHomeResponse::homeFor($user));
    }
}
