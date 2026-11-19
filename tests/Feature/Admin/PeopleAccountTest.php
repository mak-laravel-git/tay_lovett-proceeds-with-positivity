<?php

use App\Enums\UserRole;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('administrators and users are listed on separate people pages', function () {
    $admin = User::factory()->administrator()->create();
    User::factory()->count(2)->create();
    User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('accounts', 2)
            ->where('role', 'user'));

    $this->actingAs($admin)
        ->get(route('admin.administrators'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/administrators/index')
            ->has('accounts', 2)
            ->where('role', 'administrator'));
});

test('an administrator can open a user detail page', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.show', $user))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/show')
            ->where('account.id', $user->id)
            ->has('account.wallet')
            ->has('account.earnings')
            ->has('account.earnings.available_months')
            ->has('account.earnings.change'));
});

test('user detail month filter compares against previous month', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create();

    $current = LedgerEntry::query()->create([
        'user_id' => $user->id,
        'entry_type' => 'credit',
        'amount_cents' => 5000,
        'idempotency_key' => 'test-credit-current',
        'description' => 'Current month credit',
    ]);
    $current->forceFill(['created_at' => now()])->save();

    $previous = LedgerEntry::query()->create([
        'user_id' => $user->id,
        'entry_type' => 'credit',
        'amount_cents' => 2000,
        'idempotency_key' => 'test-credit-previous',
        'description' => 'Previous month credit',
    ]);
    $previous->forceFill(['created_at' => now()->subMonthNoOverflow()])->save();

    $month = now()->format('Y-m');

    $this->actingAs($admin)
        ->get(route('admin.users.show', ['person' => $user, 'month' => $month]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/show')
            ->where('account.earnings.month', $month)
            ->where('account.earnings.current.credited_cents', 5000)
            ->where('account.earnings.previous.credited_cents', 2000)
            ->where('account.earnings.change.credited_percent', 150));
});

test('an administrator detail page includes security fields', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.administrators.show', $admin))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/administrators/show')
            ->where('account.id', $admin->id)
            ->where('account.role', 'administrator')
            ->has('account.two_factor_enabled')
            ->missing('account.wallet'));
});

test('an administrator can create a user with avatar and wallet', function () {
    $admin = User::factory()->administrator()->create();
    $avatar = UploadedFile::fake()->image('avatar.jpg');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Taylor User',
            'email' => 'taylor@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'email_verified' => true,
            'avatar' => $avatar,
        ])
        ->assertRedirect();

    $user = User::query()->where('email', 'taylor@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->avatar_path)->not->toBeNull()
        ->and(Wallet::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('an administrator can create another administrator', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->post(route('admin.administrators.store'), [
            'name' => 'Second Admin',
            'email' => 'second@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'email_verified' => true,
        ])
        ->assertRedirect();

    $created = User::query()->where('email', 'second@example.com')->first();

    expect($created?->role)->toBe(UserRole::Administrator);
});

test('users can be soft deleted and restored', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect();

    expect(User::query()->find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id)?->trashed())->toBeTrue();

    $this->actingAs($admin)
        ->post(route('admin.users.restore', $user->id))
        ->assertRedirect();

    expect(User::query()->find($user->id))->not->toBeNull();
});

test('the last administrator cannot be deleted', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->from(route('admin.administrators'))
        ->delete(route('admin.administrators.destroy', $admin))
        ->assertRedirect()
        ->assertSessionHasErrors('user');

    expect($admin->fresh())->not->toBeNull();
});

test('a user can be banned and then cannot log in', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create([
        'email' => 'banned@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.ban', $user), [
            'reason' => 'Abuse',
        ])
        ->assertRedirect();

    expect($user->fresh()?->isBanned())->toBeTrue();

    auth()->logout();

    $this->post('/login', [
        'email' => 'banned@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors('email');
});

test('a user can be suspended for a number of days', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.suspend', $user), [
            'days' => 3,
            'reason' => 'Cool down',
        ])
        ->assertRedirect();

    $fresh = $user->fresh();

    expect($fresh?->isSuspended())->toBeTrue()
        ->and($fresh?->suspension_reason)->toBe('Cool down');
});

test('administrators cannot be banned', function () {
    $admin = User::factory()->administrator()->create();
    $otherAdmin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.ban', $otherAdmin), [
            'reason' => 'Nope',
        ])
        ->assertNotFound();
});

test('a normal user cannot manage people accounts', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.administrators'))
        ->assertForbidden();
});
