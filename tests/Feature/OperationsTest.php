<?php

use App\Ai\Agents\QuoteAgent;
use App\Enums\GatePurpose;
use App\Models\AdActivity;
use App\Models\AdSession;
use App\Models\AiBudgetCounter;
use App\Models\AiModel;
use App\Models\AiProviderCredential;
use App\Models\AiSetting;
use App\Models\LedgerEntry;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Services\WalletLedger;
use Illuminate\Support\Facades\Http;

test('credential secrets are not shared with the browser', function () {
    $admin = User::factory()->administrator()->create();
    $secret = 'sk-test-secret-value';

    $this->actingAs($admin)->post(route('admin.ai.providers.store'), [
        'driver' => 'openai',
        'label' => 'OpenAI A',
        'key' => $secret,
        'priority' => 1,
    ])->assertRedirect();

    $this->actingAs($admin)
        ->get(route('admin.ai.providers'))
        ->assertOk()
        ->assertDontSee($secret);

    expect(AiProviderCredential::query()->count())->toBe(1);
});

test('two credentials for the same driver can stay enabled and simulation makes no http call', function () {
    Http::preventStrayRequests();
    $admin = User::factory()->administrator()->create();

    AiModel::query()->create([
        'driver' => 'openai',
        'name' => 'gpt-test',
        'is_enabled' => true,
        'max_request_cents' => 25,
    ]);

    foreach (['A', 'B'] as $index => $label) {
        AiProviderCredential::query()->create([
            'driver' => 'openai',
            'label' => 'OpenAI '.$label,
            'priority' => $index + 1,
            'is_enabled' => true,
            'payload' => ['key' => 'fake-'.$label],
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.ai.routing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('simulation', 2));

    Http::assertNothingSent();
});

test('a connection test does not publish a quote', function () {
    QuoteAgent::fake([
        ['text' => 'Test phrase', 'category' => 'Hope'],
    ]);

    $admin = User::factory()->administrator()->create();
    $credential = AiProviderCredential::query()->create([
        'driver' => 'openai',
        'label' => 'OpenAI A',
        'priority' => 1,
        'is_enabled' => true,
        'payload' => ['key' => 'fake-key'],
    ]);
    AiModel::query()->create([
        'driver' => 'openai',
        'name' => 'gpt-test',
        'is_enabled' => true,
        'max_request_cents' => 25,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.ai.providers.test', $credential))
        ->assertRedirect();

    expect(Quote::query()->count())->toBe(0);
});

test('generation is auto-approved and a missing token count still spends the reservation', function () {
    QuoteAgent::fake([
        ['text' => 'Hope finds a way through.', 'category' => 'Hope'],
    ]);

    AiSetting::current()->update([
        'generation_enabled' => true,
        'daily_budget_cents' => 100,
        'max_attempts' => 2,
    ]);
    AiProviderCredential::query()->create([
        'driver' => 'openai',
        'label' => 'OpenAI A',
        'priority' => 1,
        'is_enabled' => true,
        'payload' => ['key' => 'fake-key'],
    ]);
    AiModel::query()->create([
        'driver' => 'openai',
        'name' => 'gpt-test',
        'is_enabled' => true,
        'max_request_cents' => 25,
    ]);

    $user = User::factory()->create();
    $category = QuoteCategory::factory()->create();
    // Quote generation is gated behind one completed survey.
    AdActivity::factory()->for($user)->approved()->create(['purpose' => GatePurpose::QuoteGeneration]);

    $this->actingAs($user)->post(route('studio.generate'), [
        'quote_category_id' => $category->id,
    ])->assertRedirect();

    $quote = Quote::query()->first();
    expect($quote->status->value)->toBe('approved')
        ->and($quote->user_id)->toBe($user->id);
    $this->get(route('quotes.public', $quote))->assertNotFound();

    expect(AiBudgetCounter::query()->first()->spent_cents)->toBe(25);
});

test('a demo advertisement does not change the wallet', function () {
    $user = User::factory()->create();
    $quote = Quote::factory()->published()->create();

    $this->actingAs($user)
        ->post(route('ads.present', $quote))
        ->assertRedirect();

    $session = AdSession::query()->first();
    $this->actingAs($user)->post(route('ads.exit', $session))->assertRedirect();

    expect($session->refresh()->state)->toBe('ad_exited')
        ->and(LedgerEntry::query()->count())->toBe(0)
        ->and(Wallet::query()->count())->toBe(0);
});

test('a withdrawal cannot exceed the available balance and a user cannot mark it paid', function () {
    $user = User::factory()->create();
    $admin = User::factory()->administrator()->create();
    app(WalletLedger::class)->post($user, 500, 'test_credit', 'test-credit-1', 'Internal test credit.');

    $this->actingAs($user)->post(route('withdrawals.store'), [
        'amount_cents' => 900,
        'destination_label' => 'PayPal label',
    ])->assertSessionHasErrors('amount');

    $this->actingAs($user)->post(route('withdrawals.store'), [
        'amount_cents' => 200,
        'destination_label' => 'PayPal label',
    ])->assertRedirect();

    $withdrawal = WithdrawalRequest::query()->first();
    expect(Wallet::query()->where('user_id', $user->id)->first()->available_cents)->toBe(300);

    $this->actingAs($user)
        ->post(route('admin.withdrawals.paid', $withdrawal), ['external_reference' => 'manual'])
        ->assertForbidden();

    $this->actingAs($admin)->post(route('admin.withdrawals.approve', $withdrawal));
    $this->actingAs($admin)->post(route('admin.withdrawals.paid', $withdrawal), [
        'external_reference' => 'manual-ref',
    ])->assertRedirect();

    expect($withdrawal->refresh()->status)->toBe('paid')
        ->and($withdrawal->external_reference)->toBe('manual-ref');
});
