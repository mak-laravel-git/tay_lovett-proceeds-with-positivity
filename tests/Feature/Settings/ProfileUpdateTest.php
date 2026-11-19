<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/profile')
            ->where('emailVerified', true)
            ->where('pendingEmail', null)
            ->where('canDelete', true));
});

test('administrators and users share the same account pages', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create();

    foreach ([$admin, $user] as $account) {
        $this->actingAs($account)->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/profile')
                ->where('auth.is_administrator', $account->isAdministrator()));
    }
});

test('the name can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Test User'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->name)->toBe('Test User');
});

test('the email cannot be changed by sending it with the name', function () {
    $user = User::factory()->create(['email' => 'keep@example.com']);

    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'New Name', 'email' => 'hijack@example.com']);

    expect($user->refresh()->email)->toBe('keep@example.com')->and($user->email_verified_at)->not->toBeNull();
});

test('a profile photo can be uploaded replaced and removed', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('me.png', 300, 300)])
        ->assertSessionHasNoErrors();

    $first = $user->refresh()->avatar_path;
    Storage::disk('public')->assertExists($first);

    $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('new.jpg', 300, 300)]);
    Storage::disk('public')->assertMissing($first);

    $second = $user->refresh()->avatar_path;
    $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertRedirect();

    Storage::disk('public')->assertMissing($second);
    expect($user->refresh()->avatar_path)->toBeNull();
});

test('svg tiny huge and non image photos are rejected', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    foreach ([
        UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        UploadedFile::fake()->image('tiny.png', 10, 10),
        UploadedFile::fake()->image('big.png', 300, 300)->size(3000),
        UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
    ] as $file) {
        $this->actingAs($user)->post(route('profile.avatar.update'), ['avatar' => $file])->assertSessionHasErrors('avatar');
    }

    expect($user->refresh()->avatar_path)->toBeNull();
});

test('changing the email needs the password and a code sent to the new address', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)->post(route('profile.email.request'), ['new_email' => 'new@example.com', 'current_password' => 'wrong'])
        ->assertSessionHasErrors('current_password');
    Notification::assertNothingSent();

    $this->actingAs($user)->post(route('profile.email.request'), ['new_email' => 'new@example.com', 'current_password' => 'password'])
        ->assertSessionHasNoErrors();

    // Nothing changes until the code is confirmed.
    expect($user->refresh()->email)->toBe('old@example.com');

    $code = sentOtpCode('new@example.com');
    $this->actingAs($user)->post(route('profile.email.verify'), ['code' => $code])->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email)->toBe('new@example.com')->and($user->email_verified_at)->not->toBeNull();
});

test('a wrong email change code leaves the email unchanged', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)->post(route('profile.email.request'), ['new_email' => 'new@example.com', 'current_password' => 'password']);
    $wrong = sentOtpCode('new@example.com') === '000000' ? '111111' : '000000';

    $this->actingAs($user)->post(route('profile.email.verify'), ['code' => $wrong])->assertSessionHasErrors('code');

    expect($user->refresh()->email)->toBe('old@example.com');
});

test('an email already in use cannot be claimed', function () {
    Notification::fake();
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.email.request'), ['new_email' => 'Taken@Example.com', 'current_password' => 'password'])
        ->assertSessionHasErrors('new_email');

    Notification::assertNothingSent();
});

test('a code another account started cannot change this account', function () {
    Notification::fake();
    $starter = User::factory()->create();
    $other = User::factory()->create(['email' => 'other@example.com']);

    $this->actingAs($starter)->post(route('profile.email.request'), ['new_email' => 'shared@example.com', 'current_password' => 'password']);
    $code = sentOtpCode('shared@example.com');

    // The second account picks the same address, which replaces the code, then tries the first account's code.
    $this->travel(61)->seconds();
    $this->actingAs($other)->post(route('profile.email.request'), ['new_email' => 'shared@example.com', 'current_password' => 'password']);
    $this->actingAs($starter)->withSession(['email_change.pending' => 'shared@example.com'])
        ->post(route('profile.email.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');

    expect($starter->refresh()->email)->not->toBe('shared@example.com');
});

test('an email change can be cancelled', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.email.request'), ['new_email' => 'new@example.com', 'current_password' => 'password']);
    $this->actingAs($user)->delete(route('profile.email.cancel'))->assertRedirect();

    $this->actingAs($user)->get(route('profile.edit'))->assertInertia(fn ($page) => $page->where('pendingEmail', null));
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    $this->assertSoftDeleted($user);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull()->and($user->fresh()->trashed())->toBeFalse();
});

test('administrators cannot delete their own account from settings', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasErrors('password');

    $this->assertAuthenticated();
    expect($admin->fresh()->trashed())->toBeFalse();
});

test('guests and restricted accounts cannot reach account pages', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));

    $banned = User::factory()->create(['banned_at' => now()]);
    $this->actingAs($banned)->get(route('profile.edit'))->assertRedirect(route('login'));
});
