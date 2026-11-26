<?php

use App\Enums\ActivityStatus;
use App\Enums\GatePurpose;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdRewardRule;
use App\Models\PointBalance;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Models\QuoteWorkflowSetting;
use App\Models\User;
use App\Services\Ads\AdGate;
use App\Services\Ads\SimulatedCompletion;
use Illuminate\Support\Facades\Http;
use Tests\Support\TapResearchFixtures;

beforeEach(function () {
    Http::preventStrayRequests();
});

function unlockFor(User $user, array $state = []): AdActivity
{
    return AdActivity::factory()->for($user)->approved()->create(['purpose' => GatePurpose::QuoteGeneration, ...$state]);
}

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

test('the gate follows the workflow rules', function () {
    $user = User::factory()->create();

    foreach ([['survey_enabled' => false], ['enabled' => false]] as $rules) {
        QuoteWorkflowSetting::current()->update(['enabled' => true, 'survey_enabled' => true, ...$rules]);

        expect(app(AdGate::class)->enabled(GatePurpose::QuoteGeneration))->toBeFalse();
        $this->actingAs($user)->getJson(route('ads.gate', 'quote_generation'))->assertJson(['enabled' => false]);
    }
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

test('studio tells the page whether the step-by-step flow is on', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('studio'))
        ->assertInertia(fn ($page) => $page
            ->where('workflow.enabled', true)
            ->where('workflow.active', null)
            ->where('workflow.disclosure', fn (string $text) => str_contains($text, 'does not earn points')));
});

test('starting a survey keeps the purpose it was started for', function () {
    AdProvider::factory()->create();
    Http::fake([
        'api.tapresearch.com/supply_api/v4/campaigns*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey('777001', 5)])),
    ]);
    $user = User::factory()->create();
    $category = QuoteCategory::factory()->create();
    // A survey for a quote is started from a run that has reached the survey step.
    $this->actingAs($user)->postJson(route('studio.workflow.store'), ['quote_category_id' => $category->id])
        ->assertJsonPath('workflow.state', 'survey');

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

test('the feed shows the users own points so a finished survey can refresh them in place', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    PointBalance::query()->create(['user_id' => $user->id, 'balance' => 30, 'lifetime_earned' => 55]);
    PointBalance::query()->create(['user_id' => $other->id, 'balance' => 999, 'lifetime_earned' => 999]);

    $this->actingAs($user)->get(route('feed'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('feed/index')
            ->where('points.balance', 30)
            ->where('points.lifetime_earned', 55));
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
