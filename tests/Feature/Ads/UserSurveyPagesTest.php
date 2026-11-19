<?php

use App\Enums\ActivityStatus;
use App\Enums\RewardStatus;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdRewardRule;
use App\Models\PointBalance;
use App\Models\PointLedgerEntry;
use App\Models\ProviderIdentity;
use App\Models\User;
use App\Services\Ads\Providers\TapResearch\TapResearchSignature;
use Illuminate\Support\Str;
use Tests\Support\TapResearchFixtures;

beforeEach(function () {
    AdProvider::factory()->create();
    AdRewardRule::query()->create(['provider_key' => null, 'activity_type' => 'survey', 'points' => 25, 'is_enabled' => true]);
});

/**
 * The browser redirect a provider sends after a survey, signed like the real one.
 *
 * @param  array<string, string>  $overrides
 */
function returnUrl(AdActivity $activity, array $overrides = [], string $secret = TapResearchFixtures::SECRET): string
{
    $passThrough = rtrim(strtr(base64_encode(json_encode(['activity_id' => $activity->id])), '+/', '-_'), '=');

    $values = array_merge([
        'status' => '1',
        'revenue' => '1.32',
        'reward' => '50',
        'tid' => 'click-1',
        'click_id' => 'c-1',
        'pass_through' => $passThrough,
    ], $overrides);

    return route('surveys.return', 'tapresearch').'?'.http_build_query([...$values, 'sech' => TapResearchSignature::signRedirect($values, $secret)]);
}

// ---------------------------------------------------------------- earnings / history

test('a user sees only their own survey history and points', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();

    AdActivity::factory()->for($me)->approved(1_000_000, 25)->create(['offer_identifier' => 'mine-1']);
    AdActivity::factory()->for($me)->create(['offer_identifier' => 'mine-2']);
    AdActivity::factory()->for($other)->approved(1_000_000, 99)->create(['offer_identifier' => 'theirs-1']);

    PointBalance::query()->create(['user_id' => $me->id, 'balance' => 25, 'lifetime_earned' => 40]);
    PointBalance::query()->create(['user_id' => $other->id, 'balance' => 99, 'lifetime_earned' => 99]);

    $response = $this->actingAs($me)->get(route('earnings'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('earnings/index')
            ->where('points.balance', 25)
            ->where('points.lifetime_earned', 40)
            ->has('activities.data', 2));

    // Nothing belonging to another user is in the response at all.
    $response->assertDontSee('theirs-1')->assertDontSee($other->email);
});

test('each history row has the survey, dates, status, points and reward status', function () {
    $user = User::factory()->create();
    AdActivity::factory()->for($user)->approved(1_320_000, 25)->create(['offer_identifier' => '555001']);

    $this->actingAs($user)->get(route('earnings'))
        ->assertInertia(fn ($page) => $page
            ->where('activities.data.0.survey', '555001')
            ->where('activities.data.0.status', 'approved')
            ->where('activities.data.0.points', 25)
            ->where('activities.data.0.reward_status', 'credited')
            ->has('activities.data.0.started_at')
            ->has('activities.data.0.completed_at'));
});

test('the user history never exposes provider revenue, transaction ids or payloads', function () {
    $user = User::factory()->create();
    $activity = AdActivity::factory()->for($user)->approved(9_876_543, 25)->create(['callback_payload' => ['secret' => 'internal-note']]);

    $response = $this->actingAs($user)->get(route('earnings'))->assertOk();

    $response->assertDontSee('9.87')->assertDontSee('9876543')->assertDontSee('internal-note')->assertDontSee($activity->provider_transaction_id);
    $response->assertInertia(fn ($page) => $page
        ->missing('activities.data.0.publisher_revenue_usd')
        ->missing('activities.data.0.provider_transaction_id')
        ->missing('activities.data.0.callback_payload'));
});

test('postback-only payouts are not listed in a user history', function () {
    $user = User::factory()->create();
    AdActivity::factory()->for($user)->create(['source' => AdActivity::SOURCE_POSTBACK_ONLY]);

    $this->actingAs($user)->get(route('earnings'))->assertInertia(fn ($page) => $page->has('activities.data', 0));
});

test('the history is paginated for users with many surveys', function () {
    $user = User::factory()->create();
    AdActivity::factory()->for($user)->count(20)->create();

    $this->actingAs($user)->get(route('earnings'))
        ->assertInertia(fn ($page) => $page->has('activities.data', 15)->where('activities.total', 20));
});

test('a new user sees an empty history and zero points', function () {
    $this->actingAs(User::factory()->create())->get(route('earnings'))
        ->assertInertia(fn ($page) => $page->where('points.balance', 0)->where('points.lifetime_earned', 0)->has('activities.data', 0));
});

test('guests are sent to sign in', function () {
    $this->get(route('earnings'))->assertRedirect(route('login'));
    $this->get(route('surveys'))->assertRedirect(route('login'));
});

test('the surveys page shows the points balance', function () {
    $user = User::factory()->create();
    PointBalance::query()->create(['user_id' => $user->id, 'balance' => 70, 'lifetime_earned' => 120]);

    $this->actingAs($user)->get(route('surveys'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('surveys/index')->where('points.balance', 70)->where('points.lifetime_earned', 120));
});

// ---------------------------------------------------------------- return page

test('coming back from a survey records the return but never approves it or awards points', function () {
    $user = User::factory()->create();
    $activity = AdActivity::factory()->for($user)->create();

    // Even a perfectly signed "status=1 / complete" redirect cannot approve anything.
    $this->get(returnUrl($activity, ['status' => '1']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('surveys/return')->where('state', 'returned')->where('activityId', $activity->id));

    $fresh = $activity->fresh();
    expect($fresh->survey_returned_at)->not->toBeNull()
        ->and($fresh->status)->toBe(ActivityStatus::Returned)
        ->and($fresh->provider_status)->toBe('1')
        ->and($fresh->reward_status)->toBe(RewardStatus::Pending)
        ->and($fresh->awarded_points)->toBe(0)
        ->and($fresh->provider_confirmed_at)->toBeNull()
        ->and($fresh->provider_transaction_id)->toBeNull()
        ->and($fresh->publisher_revenue_micros)->toBe(0)
        ->and(PointLedgerEntry::query()->count())->toBe(0)
        ->and(PointBalance::query()->count())->toBe(0);
});

test('a forged return link changes nothing', function () {
    $activity = AdActivity::factory()->create();

    $this->get(returnUrl($activity, [], 'wrong-secret'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('state', 'unverified')->where('activityId', null));

    expect($activity->fresh()->survey_returned_at)->toBeNull()
        ->and($activity->fresh()->status)->toBe(ActivityStatus::Started);

    $this->get(route('surveys.return', 'tapresearch').'?status=1&pass_through='.$activity->id)->assertOk();
    expect($activity->fresh()->survey_returned_at)->toBeNull();
});

test('returning for an unknown provider or activity is harmless', function () {
    $this->get(route('surveys.return', 'nobody').'?status=1')->assertOk()->assertInertia(fn ($page) => $page->where('state', 'unverified'));

    $ghost = AdActivity::factory()->make(['id' => (string) Str::ulid()]);
    $this->get(returnUrl($ghost))->assertOk()->assertInertia(fn ($page) => $page->where('state', 'unverified'));
});

test('a confirmed survey keeps its status when the user returns afterwards', function () {
    $user = User::factory()->create();
    ProviderIdentity::query()->create(['user_id' => $user->id, 'provider_key' => 'tapresearch', 'external_user_id' => 'uexample']);
    $activity = AdActivity::factory()->for($user)->create(['provider_user_id' => 'uexample']);

    $this->getJson(TapResearchFixtures::postbackUrl(['activity_id' => $activity->id, 'uid' => 'uexample', 'cpid' => 'cpid-return-1']))->assertOk();
    $this->get(returnUrl($activity, ['status' => '2']))->assertOk();

    $fresh = $activity->fresh();
    expect($fresh->status)->toBe(ActivityStatus::Approved)
        ->and($fresh->awarded_points)->toBe(25)
        ->and(PointLedgerEntry::query()->count())->toBe(1);
});

test('the redirect signature accepts blank values whether or not they are part of the signed string', function () {
    $values = ['status' => '40', 'revenue' => '0', 'reward' => '0', 'tid' => '', 'click_id' => 'c', 'pass_through' => 'p'];

    $withBlank = hash_hmac('sha256', implode(',', $values), 'secret');
    $withoutBlank = hash_hmac('sha256', implode(',', array_filter($values, fn ($value) => $value !== '')), 'secret');

    expect(TapResearchSignature::verifyRedirect([...$values, 'sech' => $withBlank], 'secret'))->toBeTrue()
        ->and(TapResearchSignature::verifyRedirect([...$values, 'sech' => $withoutBlank], 'secret'))->toBeTrue()
        ->and(TapResearchSignature::verifyRedirect([...$values, 'sech' => 'nope'], 'secret'))->toBeFalse()
        ->and(TapResearchSignature::verifyRedirect($values, 'secret'))->toBeFalse();
});

// ---------------------------------------------------------------- security headers

test('only the survey return page may be framed, and only by this site', function () {
    $user = User::factory()->create();
    $activity = AdActivity::factory()->for($user)->create();

    expect($this->get(returnUrl($activity))->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');

    foreach ([route('feed'), route('surveys'), route('earnings')] as $url) {
        expect($this->actingAs($user)->get($url)->headers->get('X-Frame-Options'))->toBe('DENY');
    }
});
