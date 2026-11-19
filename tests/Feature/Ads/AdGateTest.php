<?php

use App\Ai\Agents\QuoteAgent;
use App\Enums\ActivityStatus;
use App\Enums\GatePurpose;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdRewardRule;
use App\Models\AdSetting;
use App\Models\AiModel;
use App\Models\AiProviderCredential;
use App\Models\AiSetting;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Models\User;
use App\Services\Ads\AdGate;
use App\Services\Ads\SimulatedCompletion;
use Illuminate\Support\Facades\Http;
use Tests\Support\TapResearchFixtures;

beforeEach(function () {
    Http::preventStrayRequests();
});

/**
 * A working AI route, so a failure in these tests can only come from the gate.
 */
function readyAiRoute(): void
{
    QuoteAgent::fake([
        ['text' => 'Hope finds a way through.', 'category' => 'Hope'],
        ['text' => 'Courage is a quiet daily choice.', 'category' => 'Hope'],
    ]);

    AiSetting::current()->update(['generation_enabled' => true, 'daily_budget_cents' => 100, 'max_attempts' => 2]);
    AiProviderCredential::query()->create([
        'driver' => 'openai', 'label' => 'OpenAI A', 'priority' => 1, 'is_enabled' => true, 'payload' => ['key' => 'fake-key'],
    ]);
    AiModel::query()->create(['driver' => 'openai', 'name' => 'gpt-test', 'is_enabled' => true, 'max_request_cents' => 25]);
}

function unlockFor(User $user, array $state = []): AdActivity
{
    return AdActivity::factory()->for($user)->approved()->create(['purpose' => GatePurpose::QuoteGeneration, ...$state]);
}

test('a quote cannot be generated without a completed survey', function () {
    readyAiRoute();
    $user = User::factory()->create();
    $category = QuoteCategory::factory()->create();

    $this->actingAs($user)->post(route('studio.generate'), ['quote_category_id' => $category->id])
        ->assertSessionHasErrors('ad_gate');

    expect(Quote::query()->count())->toBe(0);
});

test('a completed survey pays for exactly one quote and records which one', function () {
    readyAiRoute();
    $user = User::factory()->create();
    $category = QuoteCategory::factory()->create();
    $unlock = unlockFor($user);

    $this->actingAs($user)->post(route('studio.generate'), ['quote_category_id' => $category->id])
        ->assertRedirect(route('studio'))
        ->assertSessionHasNoErrors();

    $quote = Quote::query()->firstOrFail();
    $unlock->refresh();
    expect($unlock->consumed_at)->not->toBeNull()
        ->and($unlock->consumed_quote_id)->toBe($quote->id);

    // The same survey cannot be spent again.
    $this->actingAs($user)->post(route('studio.generate'), ['quote_category_id' => $category->id])
        ->assertSessionHasErrors('ad_gate');

    expect(Quote::query()->count())->toBe(1);
});

test('a failed generation gives the unlock back', function () {
    // No AI provider is configured, so generation fails after the gate has let the request through.
    $user = User::factory()->create();
    $category = QuoteCategory::factory()->create();
    $unlock = unlockFor($user);

    $this->actingAs($user)->post(route('studio.generate'), ['quote_category_id' => $category->id])
        ->assertSessionHasErrors('generation');

    $unlock->refresh();
    expect($unlock->consumed_at)->toBeNull()
        ->and($unlock->consumed_quote_id)->toBeNull()
        ->and(app(AdGate::class)->available($user, GatePurpose::QuoteGeneration))->toBe(1);
});

test('only an approved, unspent, in-app survey for this purpose of this user unlocks a quote', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    unlockFor($other);
    unlockFor($user, ['status' => ActivityStatus::Started]);
    unlockFor($user, ['status' => ActivityStatus::Rejected]);
    unlockFor($user, ['source' => AdActivity::SOURCE_POSTBACK_ONLY]);
    unlockFor($user, ['purpose' => null]);
    unlockFor($user, ['consumed_at' => now()]);

    expect(app(AdGate::class)->available($user, GatePurpose::QuoteGeneration))->toBe(0)
        ->and(app(AdGate::class)->claim($user, GatePurpose::QuoteGeneration))->toBeNull();

    unlockFor($user);

    expect(app(AdGate::class)->available($user, GatePurpose::QuoteGeneration))->toBe(1);
});

test('two claims for a single survey cannot both succeed', function () {
    $user = User::factory()->create();
    unlockFor($user);
    $gate = app(AdGate::class);

    expect($gate->claim($user, GatePurpose::QuoteGeneration))->not->toBeNull()
        ->and($gate->claim($user, GatePurpose::QuoteGeneration))->toBeNull();
});

test('the gate can be switched off by an administrator', function () {
    readyAiRoute();
    AdSetting::current()->update(['gate_quote_generation' => false]);
    $user = User::factory()->create();
    $category = QuoteCategory::factory()->create();

    $this->actingAs($user)->post(route('studio.generate'), ['quote_category_id' => $category->id])
        ->assertSessionHasNoErrors();

    expect(Quote::query()->count())->toBe(1);
});

test('the gate status endpoint reports the signed-in users own unlocks', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    unlockFor($other);

    $this->actingAs($user)->getJson(route('ads.gate', 'quote_generation'))
        ->assertOk()
        ->assertExactJson(['enabled' => true, 'available' => 0, 'unlocked' => false]);

    unlockFor($user);

    $this->actingAs($user)->getJson(route('ads.gate', 'quote_generation'))
        ->assertOk()
        ->assertJson(['available' => 1, 'unlocked' => true]);

    $this->actingAs($user)->getJson('/ads/gate/not_a_purpose')->assertNotFound();
    auth()->logout();
    $this->getJson(route('ads.gate', 'quote_generation'))->assertUnauthorized();
});

test('studio tells the page whether the gate is on and how many unlocks are left', function () {
    $user = User::factory()->create();
    unlockFor($user);

    $this->actingAs($user)->get(route('studio'))
        ->assertInertia(fn ($page) => $page
            ->where('gate.purpose', 'quote_generation')
            ->where('gate.enabled', true)
            ->where('gate.available', 1));
});

test('starting a survey keeps the purpose it was started for', function () {
    AdProvider::factory()->create();
    Http::fake([
        'api.tapresearch.com/supply_api/v4/campaigns*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey('777001', 5)])),
    ]);
    $user = User::factory()->create();

    $list = $this->actingAs($user)->postJson(route('surveys.offers'))->assertOk()->json();

    $this->actingAs($user)->postJson(route('surveys.start'), [
        'request_id' => $list['request_id'],
        'offer_identifier' => '777001',
        'purpose' => 'quote_generation',
    ])->assertOk();

    expect(AdActivity::query()->firstOrFail()->purpose)->toBe(GatePurpose::QuoteGeneration);

    $this->actingAs($user)->postJson(route('surveys.start'), [
        'request_id' => $list['request_id'],
        'offer_identifier' => '777001',
        'purpose' => 'something_else',
    ])->assertUnprocessable();
});

test('the Google AdSense placeholder is skipped and surveys still load from TapResearch', function () {
    AdProvider::factory()->create(['priority' => 20]);
    AdProvider::factory()->create(['key' => 'google_adsense', 'label' => 'Google AdSense', 'priority' => 5, 'settings' => null]);
    Http::fake([
        'api.tapresearch.com/supply_api/v4/campaigns*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey('888001', 4)])),
    ]);

    $this->actingAs(User::factory()->create())->postJson(route('surveys.offers'))
        ->assertOk()
        ->assertJsonPath('provider', 'tapresearch')
        ->assertJsonCount(1, 'offers');
});

test('simulated completion runs the real callback pipeline and only in the local environment', function () {
    AdProvider::factory()->create();
    AdRewardRule::query()->create(['provider_key' => null, 'activity_type' => 'survey', 'points' => 25, 'is_enabled' => true]);
    $user = User::factory()->create();
    $activity = AdActivity::factory()->for($user)->create(['purpose' => GatePurpose::QuoteGeneration]);

    expect(fn () => app(SimulatedCompletion::class)->complete($activity))->toThrow(LogicException::class);
    expect($activity->refresh()->status)->toBe(ActivityStatus::Started);

    app()->detectEnvironment(fn (): string => 'local');
    app(SimulatedCompletion::class)->complete($activity);

    $activity->refresh();
    expect($activity->status)->toBe(ActivityStatus::Approved)
        ->and($activity->awarded_points)->toBe(25)
        ->and($activity->publisher_revenue_micros)->toBe(0)
        ->and(app(AdGate::class)->available($user, GatePurpose::QuoteGeneration))->toBe(1);
});

test('the simulate route does not exist outside local development', function () {
    expect(Route::has('dev.ads.simulate'))->toBeFalse();
});

test('profile and earnings show what a survey unlocked', function () {
    $user = User::factory()->create();
    $quote = Quote::factory()->approved()->create(['user_id' => $user->id, 'text' => 'A quote that a survey paid for.']);
    unlockFor($user, ['consumed_at' => now(), 'consumed_quote_id' => $quote->id]);
    unlockFor($user);

    $this->actingAs($user)->get(route('earnings'))
        ->assertInertia(fn ($page) => $page
            ->has('activities.data', 2)
            ->where('activities.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['used_for']['slug'] === $quote->slug && $row['unspent'] === false,
            ) && collect($rows)->contains(fn ($row) => $row['used_for'] === null && $row['unspent'] === true)));

    $this->actingAs($user)->get('/settings/profile')
        ->assertInertia(fn ($page) => $page
            ->where('adStats.surveys_completed', 2)
            ->where('adStats.quotes_unlocked', 1)
            ->where('adStats.unlocks_available', 1));
});
