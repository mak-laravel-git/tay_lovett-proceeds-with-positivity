<?php

use App\Console\Commands\PruneOtpCodes;
use App\Enums\OtpPurpose;
use App\Enums\UserRole;
use App\Exceptions\OtpThrottledException;
use App\Http\Responses\RolePasskeyLoginResponse;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\AppSettings;
use App\Services\Otp\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

function registrationPayload(array $overrides = []): array
{
    return [
        'name' => 'Jordan Rivera',
        'email' => 'jordan@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$overrides,
    ];
}

test('registration always creates a regular user even if a role is posted', function () {
    Notification::fake();

    $this->post(route('register.store'), registrationPayload(['role' => 'administrator', 'is_admin' => true]))
        ->assertRedirect();

    $user = User::query()->where('email', 'jordan@example.com')->firstOrFail();
    expect($user->role)->toBe(UserRole::User)->and($user->isAdministrator())->toBeFalse();
});

test('registration needs a full name, a valid email and a matching password', function () {
    $this->post(route('register.store'), registrationPayload(['name' => '']))->assertSessionHasErrors('name');
    $this->post(route('register.store'), registrationPayload(['email' => 'not-an-email']))->assertSessionHasErrors('email');
    $this->post(route('register.store'), registrationPayload(['password_confirmation' => 'different']))->assertSessionHasErrors('password');

    expect(User::query()->count())->toBe(0);
});

test('a duplicate email is refused whatever its letter case', function () {
    Notification::fake();
    User::factory()->create(['email' => 'jordan@example.com']);

    $this->post(route('register.store'), registrationPayload(['email' => 'Jordan@Example.com']))
        ->assertSessionHasErrors('email');
});

test('registration is closed when an administrator turns it off', function () {
    app(AppSettings::class)->set('authentication.registration_open', false);

    $this->get(route('register'))->assertOk()->assertInertia(fn ($page) => $page->where('registrationOpen', false));
    $this->post(route('register.store'), registrationPayload())->assertSessionHasErrors('email');

    expect(User::query()->count())->toBe(0);
});

test('a passkey sign-in sends administrators to the admin panel and others to the feed', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create();

    foreach ([[$admin, route('admin.home', absolute: false)], [$user, route('feed', absolute: false)]] as [$account, $expected]) {
        $request = Request::create('/passkeys/login', 'POST');
        $request->setUserResolver(fn () => $account);
        $request->setLaravelSession(app('session.store'));

        $response = (new RolePasskeyLoginResponse)->toResponse($request);

        expect($response->getTargetUrl())->toEndWith($expected);
    }
});

test('banned and suspended accounts cannot sign in with a passkey', function () {
    $banned = User::factory()->create(['banned_at' => now()]);
    $active = User::factory()->create();
    $request = Request::create('/passkeys/login', 'POST');
    $passkey = new Passkey;

    $allows = fn (User $user): bool => (function () use ($request, $user, $passkey) {
        $passkey->setRelation('user', $user);

        return Passkeys::allowsLogin($request, $passkey);
    })();

    expect($allows($banned))->toBeFalse()->and($allows($active))->toBeTrue();
});

test('the confirm screen tells which confirmation methods the user has', function () {
    $plain = User::factory()->create();
    $mfa = User::factory()->withTwoFactor()->create();

    $this->actingAs($plain)->get(route('password.confirm'))
        ->assertInertia(fn ($page) => $page->where('hasPasskeys', false)->where('hasTwoFactor', false));

    $this->actingAs($mfa)->get(route('password.confirm'))
        ->assertInertia(fn ($page) => $page->where('hasTwoFactor', true));
});

test('a correct authenticator code confirms access to security settings', function () {
    $this->mock(TwoFactorAuthenticationProvider::class, fn ($mock) => $mock->shouldReceive('verify')->andReturn(true));
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)->post(route('confirm-access.two-factor'), ['code' => '123456'])
        ->assertSessionHasNoErrors();

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
    $this->actingAs($user)->get(route('security.edit'))->assertOk();
});

test('a wrong authenticator code does not confirm access', function () {
    $this->mock(TwoFactorAuthenticationProvider::class, fn ($mock) => $mock->shouldReceive('verify')->andReturn(false));
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)->post(route('confirm-access.two-factor'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect(session('auth.password_confirmed_at'))->toBeNull();
    $this->actingAs($user)->get(route('security.edit'))->assertRedirect(route('password.confirm'));
});

test('a recovery code confirms access once and is then replaced', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)->post(route('confirm-access.two-factor'), ['recovery_code' => 'recovery-code-1'])
        ->assertSessionHasNoErrors();

    expect(session('auth.password_confirmed_at'))->not->toBeNull()
        ->and($user->refresh()->recoveryCodes())->not->toContain('recovery-code-1');
});

test('access cannot be confirmed with a code when two-factor is off', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('confirm-access.two-factor'), ['code' => '123456'])
        ->assertSessionHasErrors('code');
});

test('the confirm timeout setting controls how long access stays confirmed', function () {
    app(AppSettings::class)->set('authentication.confirm_timeout_minutes', 5);

    expect(config('auth.password_timeout'))->toBe(300);
});

test('pruning removes only codes that can no longer be used', function () {
    Notification::fake();
    $otp = app(OtpService::class);
    $otp->issue('old@example.com', OtpPurpose::EmailVerification);
    $otp->issue('fresh@example.com', OtpPurpose::EmailVerification);
    OtpCode::query()->where('email', 'old@example.com')->update(['expires_at' => now()->subDays(2)]);

    $this->artisan(PruneOtpCodes::class)->assertSuccessful();

    expect(OtpCode::query()->pluck('email')->all())->toBe(['fresh@example.com']);
});

test('the hourly send limit stops code floods', function () {
    Notification::fake();
    app(AppSettings::class)->set('authentication.otp_max_sends_per_hour', 2);
    app(AppSettings::class)->set('authentication.otp_resend_cooldown_seconds', 15);
    $otp = app(OtpService::class);

    $otp->issue('flood@example.com', OtpPurpose::EmailVerification);
    $this->travel(16)->seconds();
    $otp->issue('flood@example.com', OtpPurpose::EmailVerification);
    $this->travel(16)->seconds();

    expect(fn () => $otp->issue('flood@example.com', OtpPurpose::EmailVerification))
        ->toThrow(OtpThrottledException::class);
});
