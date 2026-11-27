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

test('the how to use page is public', function () {
    $this->get(route('how-to-use'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('help/how-to-use'));
});

test('a user still cannot open an admin page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.quotes'))
        ->assertForbidden();
});
