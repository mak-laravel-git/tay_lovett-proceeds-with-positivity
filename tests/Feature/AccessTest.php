<?php

use App\Models\User;

test('guests visiting the home page are sent to login', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('verified users are sent to the feed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('feed'));

    $this->actingAs($user)
        ->get(route('feed'))
        ->assertOk();
});

test('administrators are sent to the admin home', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertRedirect(route('admin.home'));
});

test('users cannot open the admin home', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.home'))
        ->assertForbidden();
});

test('administrators can open the admin home', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.home'))
        ->assertOk();
});

test('registration cannot grant the administrator role', function () {
    $this->post(route('register.store'), [
        'name' => 'Taylor Lovett',
        'email' => 'taylor@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'administrator',
    ]);

    $user = User::query()->where('email', 'taylor@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->isAdministrator())->toBeFalse();
});

test('login sends each role to its own home', function () {
    $user = User::factory()->create();
    $admin = User::factory()->administrator()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('feed', absolute: false));

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.home', absolute: false));
});
