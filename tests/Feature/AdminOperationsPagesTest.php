<?php

use App\Models\AiGenerationLog;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;

test('administrators can open moderation through settings pages', function () {
    $admin = User::factory()->administrator()->create();

    $routes = [
        'admin.quotes' => 'admin/quotes',
        'admin.ads.index' => 'admin/ads/index',
        'admin.revenue' => 'admin/revenue',
        'admin.withdrawals' => 'admin/withdrawals',
        'admin.ai.providers' => 'admin/ai/providers',
        'admin.ai.models' => 'admin/ai/models',
        'admin.ai.routing' => 'admin/ai/routing',
        'admin.ai.usage' => 'admin/ai/usage',
        'admin.ai.settings' => 'admin/ai/settings',
    ];

    foreach ($routes as $route => $component) {
        $this->actingAs($admin)
            ->get(route($route))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component($component));
    }
});

test('the old advertisements url redirects to the ads overview', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.advertisements'))
        ->assertRedirect('/admin/ads');
});

test('revenue page shows wallet totals and ledger rows without inventing earnings', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create();

    Wallet::query()->create([
        'user_id' => $user->id,
        'pending_cents' => 100,
        'available_cents' => 250,
        'reserved_cents' => 50,
    ]);

    LedgerEntry::query()->forceCreate([
        'user_id' => $user->id,
        'entry_type' => 'credit',
        'amount_cents' => 250,
        'idempotency_key' => 'ops-revenue-test',
        'description' => 'Posted credit',
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.revenue'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/revenue')
            ->where('wallet_totals.available_cents', 250)
            ->where('wallet_totals.pending_cents', 100)
            ->where('wallet_totals.reserved_cents', 50)
            ->has('entries', 1));
});

test('withdrawals page includes review stats', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create();

    WithdrawalRequest::query()->create([
        'user_id' => $user->id,
        'amount_cents' => 500,
        'status' => 'pending',
        'destination_label' => 'Test destination',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.withdrawals'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/withdrawals')
            ->where('stats.pending', 1)
            ->has('requests', 1));
});

test('ai usage page includes the requesting user name', function () {
    $admin = User::factory()->administrator()->create();
    $user = User::factory()->create(['name' => 'Maya Studio']);

    AiGenerationLog::query()->create([
        'user_id' => $user->id,
        'driver' => 'openai',
        'model' => 'gpt-test',
        'credential_label' => 'OpenAI A',
        'input_tokens' => 10,
        'output_tokens' => 8,
        'reserved_cents' => 25,
        'spent_cents' => 25,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.ai.usage'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ai/usage')
            ->has('logs', 1)
            ->where('logs.0.user.id', $user->id)
            ->where('logs.0.user.name', 'Maya Studio')
            ->where('logs.0.user.role', 'user'));
});

test('normal users cannot open operations or ai admin pages', function () {
    $user = User::factory()->create();

    foreach ([
        'admin.quotes',
        'admin.ads.index',
        'admin.ads.activity',
        'admin.ads.providers',
        'admin.ads.rewards',
        'admin.ads.requests',
        'admin.ads.callbacks',
        'admin.ads.reconciliation',
        'admin.advertisements',
        'admin.revenue',
        'admin.withdrawals',
        'admin.ai.settings',
    ] as $route) {
        $this->actingAs($user)->get(route($route))->assertForbidden();
    }
});
