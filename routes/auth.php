<?php

use App\Http\Controllers\Auth\ConfirmAccessController;
use App\Http\Controllers\Auth\EmailOtpController;
use App\Http\Controllers\Auth\PasswordResetOtpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Email-code authentication routes
|--------------------------------------------------------------------------
|
| Email verification and password reset use a 6-digit code instead of a link.
| Login, registration, two-factor and passkey routes still come from Fortify.
|
*/

Route::middleware(['auth', 'account.active'])->group(function () {
    Route::get('email/verify', [EmailOtpController::class, 'show'])->name('verification.notice');
    Route::post('email/verification-notification', [EmailOtpController::class, 'send'])
        ->middleware('throttle:otp-send')
        ->name('verification.send');
    Route::post('email/verify', [EmailOtpController::class, 'verify'])
        ->middleware('throttle:otp-verify')
        ->name('verification.verify');

    Route::post('confirm-access/two-factor', [ConfirmAccessController::class, 'twoFactor'])
        ->middleware('throttle:confirm-access')
        ->name('confirm-access.two-factor');
});

Route::middleware('guest')->group(function () {
    Route::get('forgot-password', [PasswordResetOtpController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetOtpController::class, 'sendCode'])
        ->middleware('throttle:otp-send')
        ->name('password.email');
    Route::get('forgot-password/verify', [PasswordResetOtpController::class, 'showCode'])->name('password.code');
    Route::post('forgot-password/verify', [PasswordResetOtpController::class, 'verifyCode'])
        ->middleware('throttle:otp-verify')
        ->name('password.code.verify');
    Route::post('forgot-password/resend', [PasswordResetOtpController::class, 'resend'])
        ->middleware('throttle:otp-send')
        ->name('password.code.resend');
    Route::get('reset-password', [PasswordResetOtpController::class, 'showReset'])->name('password.reset');
    Route::post('reset-password', [PasswordResetOtpController::class, 'update'])
        ->middleware('throttle:otp-verify')
        ->name('password.update');
});
