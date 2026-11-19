<?php

use App\Models\User;
use App\Notifications\OtpCodeNotification;
use Illuminate\Support\Facades\Notification;

test('sends a verification code to an unverified user', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))->assertRedirect();

    Notification::assertSentOnDemandTimes(OtpCodeNotification::class, 1);
});

test('does not send a code when the email is already verified', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('verification.send'))
        ->assertRedirect(route('feed', absolute: false));

    Notification::assertNothingSent();
});

test('guests cannot request a verification code', function () {
    $this->post(route('verification.send'))->assertRedirect(route('login'));
});
