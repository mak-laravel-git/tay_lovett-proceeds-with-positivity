<?php

use App\Models\User;

test('privacy and terms are public placeholders', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('legal/show'));

    $this->get(route('terms'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('legal/show'));
});

test('a user still cannot open an admin page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.quotes'))
        ->assertForbidden();
});
