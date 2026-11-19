<?php

use App\Enums\ActivityStatus;
use App\Enums\RewardStatus;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdProviderCall;
use App\Models\AdRequest;
use App\Models\ProviderIdentity;
use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\Support\TapResearchFixtures;

beforeEach(function () {
    Http::preventStrayRequests();
    AdProvider::factory()->create();
});

function fakeCampaigns(array $surveys): void
{
    Http::fake([
        'api.tapresearch.com/supply_api/v4/campaigns*' => Http::response(TapResearchFixtures::campaigns($surveys)),
    ]);
}

test('a user starts a survey and the attempt is stored as its own transaction', function () {
    fakeCampaigns([TapResearchFixtures::survey('555001', 6), TapResearchFixtures::survey('555002', 12)]);
    $user = User::factory()->create(['email' => 'private.person@example.com', 'name' => 'Private Person']);

    $list = $this->actingAs($user)->postJson(route('surveys.offers'))
        ->assertOk()
        ->assertJsonPath('provider', 'tapresearch')
        ->assertJsonCount(2, 'offers')
        // The survey entry URL stays on the server until the user actually starts one.
        ->assertJsonMissingPath('offers.0.entry_url')
        ->json();

    $response = $this->actingAs($user)->postJson(route('surveys.start'), [
        'request_id' => $list['request_id'],
        'offer_identifier' => '555001',
    ])->assertOk()->json();

    $activity = AdActivity::query()->findOrFail($response['activity_id']);

    expect($activity->user_id)->toBe($user->id)
        ->and($activity->provider_key)->toBe('tapresearch')
        ->and($activity->offer_identifier)->toBe('555001')
        ->and($activity->offer_length_minutes)->toBe(6)
        ->and($activity->status)->toBe(ActivityStatus::Started)
        ->and($activity->reward_status)->toBe(RewardStatus::Pending)
        ->and($activity->survey_loaded_at)->not->toBeNull()
        ->and($activity->survey_started_at)->not->toBeNull()
        ->and($activity->provider_confirmed_at)->toBeNull()
        ->and($activity->provider_transaction_id)->toBeNull()
        ->and($activity->awarded_points)->toBe(0);

    // Our activity id travels to the provider as pass-through, so its callback can name the exact attempt.
    expect($response['url'])->toStartWith('https://surveys.example.test/enter/555001?pass_through_values=');
    parse_str((string) parse_url($response['url'], PHP_URL_QUERY), $query);
    $decoded = json_decode(base64_decode(strtr($query['pass_through_values'], '-_', '+/')), true);
    expect($decoded)->toBe(['activity_id' => $activity->id]);
});

test('the provider only ever receives an opaque identifier, never personal details', function () {
    fakeCampaigns([TapResearchFixtures::survey()]);
    $user = User::factory()->create(['email' => 'private.person@example.com', 'name' => 'Private Person']);

    $this->actingAs($user)->postJson(route('surveys.offers'))->assertOk();

    $identity = ProviderIdentity::query()->where('user_id', $user->id)->firstOrFail();

    Http::assertSent(function (HttpRequest $request) use ($identity, $user): bool {
        return $request->hasHeader('X-Api-Token', 'test-api-token')
            && $request->hasHeader('X-User-Identifier', $identity->external_user_id)
            && ! str_contains($request->url().json_encode($request->headers()), $user->email)
            && ! str_contains($request->url().json_encode($request->headers()), 'Private');
    });
});

test('an opened survey is no longer offered and cannot be started twice', function () {
    fakeCampaigns([TapResearchFixtures::survey('555001')]);
    $user = User::factory()->create();

    $list = $this->actingAs($user)->postJson(route('surveys.offers'))->json();
    $payload = ['request_id' => $list['request_id'], 'offer_identifier' => '555001'];

    $this->actingAs($user)->postJson(route('surveys.start'), $payload)->assertOk();
    $this->actingAs($user)->postJson(route('surveys.start'), $payload)->assertStatus(410);

    expect(AdActivity::query()->count())->toBe(1);
});

test('a survey list cannot be used by a different user', function () {
    fakeCampaigns([TapResearchFixtures::survey('555001')]);
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $list = $this->actingAs($owner)->postJson(route('surveys.offers'))->json();

    $this->actingAs($other)->postJson(route('surveys.start'), [
        'request_id' => $list['request_id'],
        'offer_identifier' => '555001',
    ])->assertStatus(410);

    expect(AdActivity::query()->count())->toBe(0);
});

test('a survey that was not in the list cannot be started', function () {
    fakeCampaigns([TapResearchFixtures::survey('555001')]);
    $user = User::factory()->create();

    $list = $this->actingAs($user)->postJson(route('surveys.offers'))->json();

    $this->actingAs($user)->postJson(route('surveys.start'), [
        'request_id' => $list['request_id'],
        'offer_identifier' => 'made-up',
    ])->assertStatus(410);
});

test('the provider request and its outcome are logged', function () {
    fakeCampaigns([TapResearchFixtures::survey()]);
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('surveys.offers'))->assertOk();

    $request = AdRequest::query()->firstOrFail();
    $call = AdProviderCall::query()->firstOrFail();

    expect($request->user_id)->toBe($user->id)
        ->and($request->outcome)->toBe('success')
        ->and($request->resolved_provider_key)->toBe('tapresearch')
        ->and($request->offer_count)->toBe(1)
        ->and($call->provider_key)->toBe('tapresearch')
        ->and($call->operation)->toBe('fetch_offers')
        ->and($call->outcome->value)->toBe('success')
        ->and($call->http_status)->toBe(200);
});

test('the status endpoint only answers for the owner of the activity', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $activity = AdActivity::factory()->for($owner)->approved(points: 10)->create();

    $this->actingAs($owner)->getJson(route('surveys.status', $activity))
        ->assertOk()
        ->assertJson(['status' => 'approved', 'reward_status' => 'credited', 'points' => 10]);

    $this->actingAs($other)->getJson(route('surveys.status', $activity))->assertNotFound();
});

test('guests cannot list or start surveys', function () {
    $this->postJson(route('surveys.offers'))->assertUnauthorized();
    $this->postJson(route('surveys.start'), [])->assertUnauthorized();
});
