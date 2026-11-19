<?php

namespace App\Http\Controllers\Settings;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AvatarUpdateRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\Otp\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the account profile page. Administrators and users share it, the layout follows the role.
     */
    public function edit(Request $request, OtpService $otp): Response
    {
        $user = $request->user();
        $pending = $request->session()->get(EmailChangeController::SESSION_KEY);

        return Inertia::render('settings/profile', [
            'emailVerified' => $user->hasVerifiedEmail(),
            'memberSince' => $user->created_at?->toIso8601String(),
            'pendingEmail' => is_string($pending) ? $pending : null,
            'emailCooldown' => is_string($pending) ? $otp->cooldownRemaining($pending, OtpPurpose::EmailChange) : 0,
            'expiresInMinutes' => $otp->expiryMinutes(),
            'canDelete' => ! $user->isAdministrator(),
        ]);
    }

    /**
     * Update the display name. The email address changes only after a code sent to the new address is confirmed.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->forceFill(['name' => $request->validated('name')])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    public function updateAvatar(AvatarUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (filled($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill([
            'avatar_path' => $request->file('avatar')->store('avatars/'.$user->id, 'public'),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo updated.')]);

        return back();
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (filled($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo removed.')]);

        return back();
    }

    /**
     * Delete the user's own account. Administrators are removed by another administrator so the panel is never left without one.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdministrator()) {
            return back()->withErrors(['password' => 'Administrator accounts can only be removed by another administrator.']);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
