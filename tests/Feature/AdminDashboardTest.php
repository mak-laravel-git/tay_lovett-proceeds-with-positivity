<?php

use App\Models\Quote;
use App\Models\User;

test('administrators see the overview with chart data', function () {
    $admin = User::factory()->administrator()->create();
    Quote::factory()->published()->create();
    Quote::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/home')
            ->has('stats')
            ->has('generationTrend', 7)
            ->has('quoteStatusChart', 5)
            ->where('stats.published_quotes', 1)
            ->where('stats.pending_quotes', 1));
});

test('users page stays inside the admin shell component tree', function () {
    $admin = User::factory()->administrator()->create();
    User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('accounts', 2)
            ->where('role', 'user'));
});

test('a normal user cannot open the admin overview or users page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.home'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.administrators'))->assertForbidden();
});
