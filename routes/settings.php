<?php

use App\Http\Controllers\Settings\EmailChangeController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'account.active'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('settings/profile/avatar', [ProfileController::class, 'updateAvatar'])
        ->middleware('throttle:10,1')
        ->name('profile.avatar.update');
    Route::delete('settings/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

    Route::post('settings/profile/email', [EmailChangeController::class, 'request'])
        ->middleware('throttle:otp-send')
        ->name('profile.email.request');
    Route::post('settings/profile/email/resend', [EmailChangeController::class, 'resend'])
        ->middleware('throttle:otp-send')
        ->name('profile.email.resend');
    Route::post('settings/profile/email/verify', [EmailChangeController::class, 'verify'])
        ->middleware('throttle:otp-verify')
        ->name('profile.email.verify');
    Route::delete('settings/profile/email', [EmailChangeController::class, 'cancel'])->name('profile.email.cancel');
});

Route::middleware(['auth', 'account.active', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
