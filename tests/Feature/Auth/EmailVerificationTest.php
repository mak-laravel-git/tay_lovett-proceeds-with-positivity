<?php

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\OtpCodeNotification;
use App\Services\AppSettings;
use App\Services\Otp\OtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

test('the verification screen shows a masked email and no code yet', function () {
    $user = User::factory()->unverified()->create(['email' => 'jordan@example.com']);

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/verify-otp')
            ->where('email', 'j*****@example.com')
            ->where('hasActiveCode', false));
});

test('unverified users are redirected to the email verification prompt', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('security.edit'))
        ->assertRedirect(route('verification.notice'));
});

test('registering emails a code and a correct code verifies the account', function () {
    Notification::fake();
    Event::fake([Verified::class]);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    expect($user->hasVerifiedEmail())->toBeFalse();

    $code = sentOtpCode('test@example.com');

    $this->actingAs($user)->post(route('verification.verify'), ['code' => $code])
        ->assertRedirect(route('feed', absolute: false));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

test('a wrong code is rejected and the account stays unverified', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->post(route('verification.send'));
    $real = sentOtpCode($user->email);
    $wrong = $real === '000000' ? '111111' : '000000';

    $this->actingAs($user)->post(route('verification.verify'), ['code' => $wrong])
        ->assertSessionHasErrors('code');

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('a code can only be used once', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->post(route('verification.send'));
    $code = sentOtpCode($user->email);

    $this->actingAs($user)->post(route('verification.verify'), ['code' => $code]);
    $user->forceFill(['email_verified_at' => null])->save();

    $this->actingAs($user)->post(route('verification.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');
});

test('too many wrong guesses cancel the code even when the next guess is right', function () {
    Notification::fake();
    app(AppSettings::class)->set('authentication.otp_max_attempts', 3);
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->post(route('verification.send'));
    $code = sentOtpCode($user->email);
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 3) as $ignored) {
        $this->actingAs($user)->post(route('verification.verify'), ['code' => $wrong]);
    }

    $this->actingAs($user)->post(route('verification.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('an expired code is rejected', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->post(route('verification.send'));
    $code = sentOtpCode($user->email);

    $this->travel(11)->minutes();

    $this->actingAs($user)->post(route('verification.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');
});

test('resending is blocked during the cooldown and the page reports the wait', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('verification.send'))->assertSessionHasErrors('code');

    Notification::assertSentOnDemandTimes(OtpCodeNotification::class, 1);

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertInertia(fn ($page) => $page->where('hasActiveCode', true)->where('cooldown', fn ($seconds) => $seconds > 0 && $seconds <= 60));

    $this->travel(61)->seconds();
    $this->actingAs($user)->post(route('verification.send'))->assertSessionHasNoErrors();
    Notification::assertSentOnDemandTimes(OtpCodeNotification::class, 2);
});

test('only an email address owner can read the code and the code is never stored as plain text', function () {
    Notification::fake();
    $otp = app(OtpService::class);

    $code = $otp->issue('Someone@Example.com', OtpPurpose::EmailVerification);
    $row = OtpCode::query()->firstOrFail();

    expect($row->email)->toBe('someone@example.com')
        ->and($row->code_hash)->not->toContain($code)
        ->and(strlen($row->code_hash))->toBe(64)
        ->and($code)->toMatch('/^\d{6}$/');
});

test('a malformed code fails validation before it counts as a guess', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.verify'), ['code' => 'abc'])
        ->assertSessionHasErrors('code');
});

test('a verified user is sent away from the verification screen', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('verification.notice'))->assertRedirect(route('feed', absolute: false));
});

test('a failed email send never breaks registration', function () {
    Notification::shouldReceive('route')->andThrow(new RuntimeException('smtp down'));

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'down@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    expect(User::query()->where('email', 'down@example.com')->exists())->toBeTrue();
});
