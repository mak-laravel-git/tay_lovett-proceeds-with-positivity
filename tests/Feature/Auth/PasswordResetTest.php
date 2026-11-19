<?php

use App\Models\User;
use App\Notifications\OtpCodeNotification;
use App\Services\AppSettings;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('forgot-cooldown:'.sha1('known@example.com'));
});

test('the forgot password screen can be rendered', function () {
    $this->get(route('password.request'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/forgot-password'));
});

test('known and unknown emails get exactly the same response', function () {
    Notification::fake();
    User::factory()->create(['email' => 'known@example.com']);

    $known = $this->post(route('password.email'), ['email' => 'known@example.com']);
    $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

    $known->assertRedirect(route('password.code'));
    $unknown->assertRedirect(route('password.code'));
    expect(session('status'))->toBe('If an account exists for that email, a code is on its way.');

    Notification::assertSentOnDemandTimes(OtpCodeNotification::class, 1);
});

test('banned and suspended accounts receive no code', function () {
    Notification::fake();
    User::factory()->create(['email' => 'banned@example.com', 'banned_at' => now()]);

    $this->post(route('password.email'), ['email' => 'banned@example.com'])->assertRedirect(route('password.code'));

    Notification::assertNothingSent();
});

test('the code screen needs a started request', function () {
    $this->get(route('password.code'))->assertRedirect(route('password.request'));
    $this->get(route('password.reset'))->assertRedirect(route('password.request'));
    $this->post(route('password.update'), ['password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->assertRedirect(route('password.request'));
});

test('a correct code opens the reset form and a new password signs the user out everywhere', function () {
    Notification::fake();
    Event::fake([PasswordReset::class]);
    $user = User::factory()->create(['email' => 'known@example.com']);
    $oldToken = $user->remember_token;

    $this->post(route('password.email'), ['email' => 'known@example.com']);
    $code = sentOtpCode('known@example.com');

    $this->post(route('password.code.verify'), ['code' => $code])->assertRedirect(route('password.reset'));
    $this->get(route('password.reset'))->assertOk()->assertInertia(fn ($page) => $page->component('auth/reset-password'));

    $this->post(route('password.update'), [
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ])->assertRedirect(route('login'));

    $user->refresh();
    expect(Hash::check('a-brand-new-password', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($oldToken);
    Event::assertDispatched(PasswordReset::class);

    // The reset session is single use.
    $this->get(route('password.reset'))->assertRedirect(route('password.request'));

    // The same code cannot be replayed.
    $this->post(route('password.email'), ['email' => 'known@example.com']);
    $this->post(route('password.code.verify'), ['code' => $code])->assertSessionHasErrors('code');
});

test('a wrong code gives a generic message that is identical for unknown emails', function () {
    Notification::fake();
    User::factory()->create(['email' => 'known@example.com']);

    $this->post(route('password.email'), ['email' => 'known@example.com']);
    $known = $this->post(route('password.code.verify'), ['code' => '000000']);
    $knownMessage = session('errors')->first('code');

    $this->flushSession();
    $this->post(route('password.email'), ['email' => 'nobody@example.com']);
    $this->post(route('password.code.verify'), ['code' => '000000']);

    expect(session('errors')->first('code'))->toBe($knownMessage)
        ->and($knownMessage)->toBe('That code is not correct or has expired.');
});

test('repeated wrong codes lock the attempt the same way for known and unknown emails', function () {
    Notification::fake();
    app(AppSettings::class)->set('authentication.otp_max_attempts', 3);
    User::factory()->create(['email' => 'known@example.com']);

    foreach (['known@example.com', 'nobody@example.com'] as $email) {
        $this->flushSession();
        RateLimiter::clear('forgot-cooldown:'.sha1($email));
        $this->post(route('password.email'), ['email' => $email]);

        foreach (range(1, 3) as $ignored) {
            $this->post(route('password.code.verify'), ['code' => '000000']);
        }

        $this->post(route('password.code.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        expect(session('errors')->first('code'))->toBe('Too many wrong attempts. Request a new code.');
    }
});

test('asking again inside the cooldown does not send a second code and looks the same', function () {
    Notification::fake();
    User::factory()->create(['email' => 'known@example.com']);

    $this->post(route('password.email'), ['email' => 'known@example.com'])->assertRedirect(route('password.code'));
    $this->post(route('password.email'), ['email' => 'known@example.com'])->assertRedirect(route('password.code'));

    Notification::assertSentOnDemandTimes(OtpCodeNotification::class, 1);
});

test('the reset password form enforces the password rules and confirmation', function () {
    Notification::fake();
    User::factory()->create(['email' => 'known@example.com']);
    $this->post(route('password.email'), ['email' => 'known@example.com']);
    $this->post(route('password.code.verify'), ['code' => sentOtpCode('known@example.com')]);

    $this->post(route('password.update'), [
        'password' => 'one-password-here',
        'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors('password');
});

test('an expired reset session is refused', function () {
    Notification::fake();
    User::factory()->create(['email' => 'known@example.com']);
    $this->post(route('password.email'), ['email' => 'known@example.com']);
    $this->post(route('password.code.verify'), ['code' => sentOtpCode('known@example.com')]);

    $this->travel(16)->minutes();

    $this->get(route('password.reset'))->assertRedirect(route('password.request'));
});

test('authenticated users cannot use the guest reset pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('password.request'))->assertRedirect();
});
