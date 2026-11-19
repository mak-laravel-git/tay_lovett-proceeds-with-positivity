<?php

use App\Models\User;

afterEach(function () {
    // Never let a failed assertion leave the real app stuck in maintenance mode for the next test or for dev.
    if (app()->maintenanceMode()->active()) {
        app()->maintenanceMode()->deactivate();
    }
});

test('an administrator can turn maintenance mode on and it blocks guests and ordinary users', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.maintenance.enable'), [
        'title' => 'Back soon',
        'message' => 'We are improving things.',
    ])->assertRedirect();

    expect(app()->maintenanceMode()->active())->toBeTrue();

    $this->get('/')->assertStatus(503)->assertSee('Back soon');
    $this->actingAs($user)->get(route('feed'))->assertStatus(503);
});

test('administrators and the login page stay reachable while the site is down', function () {
    $admin = User::factory()->administrator()->create();
    $this->actingAs($admin)->post(route('admin.maintenance.enable'), ['title' => 'Down']);
    $this->actingAs($admin)->get(route('admin.home'))->assertOk();
    $this->actingAs($admin)->get(route('admin.settings'))->assertOk();

    // A fresh, unauthenticated request: actingAs() above keeps the session logged in for this test case,
    // so logging out first is the only way to prove the login page itself is reachable by a guest.
    $this->app['auth']->guard()->logout();
    $this->flushSession();
    $this->get(route('login'))->assertOk();
});

test('the secret link grants full access and the admin can turn maintenance mode back off', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.maintenance.enable'), ['title' => 'Down']);
    $secret = app()->maintenanceMode()->data()['secret'];

    $bypass = $this->get('/'.$secret);
    $bypass->assertRedirect('/');
    $cookie = collect($bypass->headers->getCookies())
        ->first(fn ($item) => $item->getName() === 'laravel_maintenance')
        ->getValue();

    // This cookie is deliberately not Laravel-encrypted (see PreventRequestsDuringMaintenance, which reads it
    // before EncryptCookies ever runs), so it must be sent through the test client unencrypted too.
    $this->withUnencryptedCookies(['laravel_maintenance' => $cookie])
        ->actingAs($user)
        ->get(route('feed'))
        ->assertOk();

    $this->actingAs($admin)->post(route('admin.maintenance.disable'))->assertRedirect();
    expect(app()->maintenanceMode()->active())->toBeFalse();

    $this->actingAs($user)->get(route('feed'))->assertOk();
});

test('the settings page reports the current maintenance status and bypass link', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->get(route('admin.settings'))
        ->assertInertia(fn ($page) => $page->where('maintenanceStatus.active', false)->where('maintenanceStatus.bypassUrl', null));

    $this->actingAs($admin)->post(route('admin.maintenance.enable'), ['title' => 'Down for a bit']);

    $this->actingAs($admin)->get(route('admin.settings'))
        ->assertInertia(fn ($page) => $page
            ->where('maintenanceStatus.active', true)
            ->where('maintenanceStatus.title', 'Down for a bit')
            ->where('maintenanceStatus.bypassUrl', fn ($url) => str_contains((string) $url, '/')));
});

test('only an administrator can turn maintenance mode on or off', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.maintenance.enable'), ['title' => 'x'])->assertForbidden();
    $this->actingAs($user)->post(route('admin.maintenance.disable'))->assertForbidden();

    expect(app()->maintenanceMode()->active())->toBeFalse();
});

test('a blank title is rejected', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->post(route('admin.maintenance.enable'), ['title' => ''])
        ->assertSessionHasErrors('title');

    expect(app()->maintenanceMode()->active())->toBeFalse();
});
