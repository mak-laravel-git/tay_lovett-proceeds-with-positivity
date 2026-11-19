<?php

use App\Enums\ActivityType;
use App\Models\AdProvider;
use App\Models\AdProviderCall;
use App\Models\AdRequest;
use App\Models\User;
use App\Services\Ads\AdProviderManager;
use App\Services\Ads\AdProviderRegistry;
use App\Services\Ads\Data\Offer;
use App\Services\Ads\Data\OfferBundle;
use App\Services\Ads\Data\OfferResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeAdapter;
use Tests\Support\TapResearchFixtures;

beforeEach(function () {
    Http::preventStrayRequests();
    Cache::flush();

    $this->user = User::factory()->create();
    $this->manager = app(AdProviderManager::class);

    // The real TapResearch adapter at priority 10, plus a stand-in second provider at priority 20.
    AdProvider::factory()->create(['key' => 'tapresearch', 'priority' => 10]);
    $this->second = AdProvider::factory()->create(['key' => 'alpha', 'label' => 'Alpha', 'priority' => 20, 'settings' => ['api_token' => 'x', 'api_secret' => 'y']]);
});

function registerFake(string $key, OfferResult $result, bool $configured = true): FakeAdapter
{
    $adapter = new FakeAdapter($key, $result, $configured);
    app(AdProviderRegistry::class)->register($adapter);

    return $adapter;
}

function alphaOffers(): OfferResult
{
    return OfferResult::success([new Offer('alpha-1', 'https://alpha.example.test/1', 5)]);
}

function ask(): OfferBundle
{
    return test()->manager->requestOffers(test()->user, ActivityType::Survey, '203.0.113.9', 2);
}

test('the highest-priority provider answers first and later providers are not called', function () {
    Http::fake(['*/campaigns*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey('t-1')]))]);
    $alpha = registerFake('alpha', alphaOffers());

    $bundle = ask();

    expect($bundle->providerKey)->toBe('tapresearch')
        ->and($bundle->offers)->toHaveCount(1)
        ->and($alpha->calls)->toBe(0)
        ->and(AdProviderCall::query()->count())->toBe(1);
});

test('a failing provider falls back to the next enabled provider by priority', function () {
    Http::fake(['*/campaigns*' => Http::response(['error' => 'down'], 503)]);
    $alpha = registerFake('alpha', alphaOffers());

    $bundle = ask();

    expect($bundle->providerKey)->toBe('alpha')
        ->and($alpha->calls)->toBe(1);

    $calls = AdProviderCall::query()->orderBy('position')->get();
    expect($calls)->toHaveCount(2)
        ->and($calls[0]->provider_key)->toBe('tapresearch')
        ->and($calls[0]->outcome->value)->toBe('failed')
        ->and($calls[0]->http_status)->toBe(503)
        ->and($calls[0]->error_category)->toBe('provider_unavailable')
        ->and($calls[1]->provider_key)->toBe('alpha')
        ->and($calls[1]->outcome->value)->toBe('success');

    $request = AdRequest::query()->sole();
    expect($request->outcome)->toBe('success')
        ->and($request->resolved_provider_key)->toBe('alpha')
        ->and($request->attempt_count)->toBe(2);
});

test('a provider with nothing to show falls back to the next one', function () {
    Http::fake(['*/campaigns*' => Http::response(TapResearchFixtures::campaigns([]))]);
    registerFake('alpha', alphaOffers());

    expect(ask()->providerKey)->toBe('alpha')
        ->and(AdProviderCall::query()->orderBy('position')->first()->outcome->value)->toBe('empty');
});

test('an ineligible user falls back to the next provider', function () {
    Http::fake(['*/campaigns*' => Http::response(TapResearchFixtures::campaigns([], false, 4))]);
    registerFake('alpha', alphaOffers());

    $bundle = ask();

    $first = AdProviderCall::query()->orderBy('position')->first();
    expect($bundle->providerKey)->toBe('alpha')
        ->and($first->outcome->value)->toBe('ineligible')
        ->and($first->error_category)->toBe('ineligible_reason_4');
});

test('a network error or timeout falls back too', function () {
    Http::fake(['*/campaigns*' => fn () => throw new ConnectionException('timed out')]);
    registerFake('alpha', alphaOffers());

    expect(ask()->providerKey)->toBe('alpha')
        ->and(AdProviderCall::query()->orderBy('position')->first()->error_category)->toBe('connection_error');
});

test('every provider failing ends the chain with no fill', function () {
    Http::fake(['*/campaigns*' => Http::response([], 500)]);
    registerFake('alpha', OfferResult::failed('provider_error', 500));

    $bundle = ask();

    expect($bundle->providerKey)->toBeNull()
        ->and($bundle->hasOffers())->toBeFalse();

    $request = AdRequest::query()->sole();
    expect($request->outcome)->toBe('no_fill')
        ->and($request->attempt_count)->toBe(2)
        ->and($request->resolved_provider_key)->toBeNull();
});

test('disabled providers are never called', function () {
    Http::fake(['*/campaigns*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey()]))]);
    AdProvider::query()->where('key', 'tapresearch')->update(['is_enabled' => false]);
    $alpha = registerFake('alpha', alphaOffers());

    expect(ask()->providerKey)->toBe('alpha')
        ->and($alpha->calls)->toBe(1);

    Http::assertNothingSent();
});

test('changing the priority changes who is asked first', function () {
    Http::fake(['*/campaigns*' => Http::response(TapResearchFixtures::campaigns([TapResearchFixtures::survey()]))]);
    $alpha = registerFake('alpha', alphaOffers());

    AdProvider::query()->where('key', 'alpha')->update(['priority' => 1]);

    expect(ask()->providerKey)->toBe('alpha');
    Http::assertNothingSent();
    expect($alpha->calls)->toBe(1);
});

test('a provider without complete credentials is skipped and logged', function () {
    Http::fake();
    AdProvider::query()->where('key', 'tapresearch')->update(['settings' => null]);
    registerFake('alpha', alphaOffers());

    expect(ask()->providerKey)->toBe('alpha');

    $first = AdProviderCall::query()->orderBy('position')->first();
    expect($first->provider_key)->toBe('tapresearch')
        ->and($first->outcome->value)->toBe('skipped')
        ->and($first->error_category)->toBe('not_configured');

    Http::assertNothingSent();
});

test('a provider that keeps failing is rested briefly instead of being hammered', function () {
    Http::fake(['*/campaigns*' => Http::response([], 503)]);
    registerFake('alpha', alphaOffers());

    foreach (range(1, 5) as $_) {
        ask();
    }

    Http::assertSentCount(5);

    // Now cooling down: the sixth request goes straight to the next provider.
    ask();
    Http::assertSentCount(5);

    $skipped = AdProviderCall::query()->where('outcome', 'skipped')->latest('id')->first();
    expect($skipped->provider_key)->toBe('tapresearch')
        ->and($skipped->error_category)->toBe('cooling_down');
});

test('health is recorded on the provider after each call', function () {
    Http::fake(['*/campaigns*' => Http::sequence()
        ->push(TapResearchFixtures::campaigns([TapResearchFixtures::survey()]))
        ->push([], 401)]);

    ask();

    $provider = AdProvider::query()->where('key', 'tapresearch')->first();
    expect($provider->health)->toBe('healthy')
        ->and($provider->last_success_at)->not->toBeNull();

    registerFake('alpha', alphaOffers());
    ask();

    expect($provider->fresh()->health)->toBe('unhealthy')
        ->and($provider->fresh()->last_error_category)->toBe('invalid_credentials');
});
