<?php

use App\Enums\ActivityStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\RewardStatus;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdProviderAudit;
use App\Models\AdProviderCall;
use App\Models\AdProviderPayout;
use App\Models\AdReconciliation;
use App\Models\AdRewardRule;
use App\Models\AdSetting;
use App\Models\ProviderCallback;
use App\Models\ProviderIdentity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Support\TapResearchFixtures;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->admin = User::factory()->administrator()->create();
    $this->provider = AdProvider::factory()->withReporting()->create();
});

// ---------------------------------------------------------------- access

test('every ads admin page rejects normal users and guests', function () {
    $user = User::factory()->create();
    $activity = AdActivity::factory()->create();
    $reconciliation = AdReconciliation::query()->create([
        'provider_key' => 'tapresearch', 'report_date' => '2026-11-10', 'timezone' => 'America/New_York',
        'status' => ReconciliationStatus::Matched, 'local_revenue_micros' => 0,
    ]);

    $urls = [
        route('admin.ads.index'),
        route('admin.ads.activity'),
        route('admin.ads.activity.show', $activity),
        route('admin.ads.providers'),
        route('admin.ads.rewards'),
        route('admin.ads.requests'),
        route('admin.ads.callbacks'),
        route('admin.ads.reconciliation'),
        route('admin.ads.reconciliation.show', $reconciliation),
    ];

    foreach ($urls as $url) {
        $this->actingAs($user)->get($url)->assertForbidden();
        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));
    }

    $this->actingAs($user)->put(route('admin.ads.providers.update', $this->provider), ['is_enabled' => false])->assertForbidden();
    $this->actingAs($user)->post(route('admin.ads.rewards.store'), ['activity_type' => 'survey', 'points' => 5])->assertForbidden();
    $this->actingAs($user)->post(route('admin.ads.reconciliation.run'), [])->assertForbidden();
    $this->actingAs($user)->post(route('admin.ads.reconciliation.payouts'), [])->assertForbidden();
});

// ---------------------------------------------------------------- overview

test('the overview keeps recorded, provider-reported and settled revenue apart', function () {
    $timezone = 'America/New_York';
    Carbon::setTestNow(CarbonImmutable::parse('2026-11-12 12:00:00', $timezone));
    AdSetting::current();

    // Recorded from postbacks: $1.32 today, $2.00 yesterday.
    AdActivity::factory()->approved(1_320_000, 25)->create(['provider_confirmed_at' => CarbonImmutable::parse('2026-11-12 09:00:00', $timezone)->utc()]);
    AdActivity::factory()->approved(2_000_000, 25)->create(['provider_confirmed_at' => CarbonImmutable::parse('2026-11-11 09:00:00', $timezone)->utc()]);

    // Provider-reported for yesterday only.
    AdReconciliation::query()->create([
        'provider_key' => 'tapresearch', 'report_date' => '2026-11-11', 'timezone' => $timezone,
        'provider_revenue_micros' => 2_100_000, 'local_revenue_micros' => 2_000_000, 'difference_micros' => 100_000,
        'status' => ReconciliationStatus::Discrepancy, 'fetched_at' => now(), 'country_breakdown' => ['US' => 2_100_000],
    ]);

    // Settled: entered by hand.
    AdProviderPayout::query()->create([
        'provider_key' => 'tapresearch', 'period_start' => '2026-11-01', 'period_end' => '2026-11-07',
        'amount_micros' => 5_500_000, 'received_at' => '2026-11-10', 'recorded_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->get(route('admin.ads.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ads/index')
            ->where('local_revenue.today_usd', '1.32')
            ->where('local_revenue.yesterday_usd', '2.00')
            ->where('local_revenue.month_usd', '3.32')
            ->where('provider_revenue.yesterday_usd', '2.10')
            ->where('provider_revenue.yesterday_status', 'discrepancy')
            ->where('settled_revenue.recorded', true)
            ->where('settled_revenue.total_usd', '5.50')
            ->where('counts.approved', 2)
            ->where('points_distributed', 50)
            ->where('countries.0.country', 'US')
            ->has('daily_chart', 14)
            ->has('providers', 1));
});

test('provider-reported and settled revenue are empty rather than invented when nothing exists', function () {
    $this->actingAs($this->admin)->get(route('admin.ads.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('provider_revenue.yesterday_usd', null)
            ->where('provider_revenue.month_usd', null)
            ->where('settled_revenue.recorded', false)
            ->where('last_reconciled_at', null)
            ->where('estimated_remaining_usd', null)
            ->where('point_value_set', false));
});

test('estimated remaining revenue needs a point value and is otherwise not guessed', function () {
    AdActivity::factory()->approved(1_000_000, 100)->create(['provider_confirmed_at' => now()]);

    // 1 point = $0.004  ->  100 points = $0.40  ->  $1.00 - $0.40 = $0.60
    AdSetting::current()->update(['point_usd_value_micros' => 4_000]);

    $this->actingAs($this->admin)->get(route('admin.ads.index'))
        ->assertInertia(fn ($page) => $page->where('estimated_remaining_usd', '0.60')->where('point_value_set', true));
});

test('the overview counts pending confirmations and unmatched payouts separately', function () {
    AdActivity::factory()->count(2)->create(); // started, waiting for the provider
    AdActivity::factory()->create(['status' => ActivityStatus::Returned]);
    AdActivity::factory()->approved()->create();
    AdActivity::factory()->create(['status' => ActivityStatus::Rejected, 'reward_status' => RewardStatus::NotEligible]);
    AdActivity::factory()->create(['source' => AdActivity::SOURCE_POSTBACK_ONLY, 'user_id' => null, 'status' => ActivityStatus::Approved]);

    $this->actingAs($this->admin)->get(route('admin.ads.index'))
        ->assertInertia(fn ($page) => $page
            ->where('counts.pending', 3)
            ->where('counts.rejected', 1)
            ->where('counts.approved', 2)
            ->where('counts.unmatched', 1));
});

// ---------------------------------------------------------------- activity log

test('administrators can search and filter every survey transaction', function () {
    $alice = User::factory()->create(['name' => 'Alice Example', 'email' => 'alice@example.com']);
    $bob = User::factory()->create(['name' => 'Bob Example', 'email' => 'bob@example.com']);

    $approved = AdActivity::factory()->for($alice)->approved()->create(['offer_identifier' => '111111', 'created_at' => '2026-11-10 10:00:00']);
    $pending = AdActivity::factory()->for($bob)->create(['offer_identifier' => '222222', 'created_at' => '2026-11-11 10:00:00']);
    $rejected = AdActivity::factory()->for($alice)->create(['offer_identifier' => '333333', 'status' => ActivityStatus::Rejected, 'reward_status' => RewardStatus::NotEligible, 'created_at' => '2026-11-12 10:00:00']);

    $ids = fn (array $query) => collect(
        $this->actingAs($this->admin)->get(route('admin.ads.activity', $query))->assertOk()->viewData('page')['props']['activities']['data'],
    )->pluck('id')->sort()->values()->all();

    $all = collect([$approved->id, $pending->id, $rejected->id])->sort()->values()->all();

    expect($ids([]))->toBe($all)
        ->and($ids(['user' => $alice->id]))->toBe(collect([$approved->id, $rejected->id])->sort()->values()->all())
        ->and($ids(['survey' => '222222']))->toBe([$pending->id])
        ->and($ids(['provider' => 'tapresearch']))->toBe($all)
        ->and($ids(['provider' => 'someone-else']))->toBe([])
        ->and($ids(['status' => 'approved']))->toBe([$approved->id])
        ->and($ids(['status' => 'rejected']))->toBe([$rejected->id])
        ->and($ids(['reward_status' => 'credited']))->toBe([$approved->id])
        ->and($ids(['reward_status' => 'pending']))->toBe([$pending->id])
        ->and($ids(['from' => '2026-11-11', 'to' => '2026-11-11']))->toBe([$pending->id])
        ->and($ids(['from' => '2026-11-11']))->toBe(collect([$pending->id, $rejected->id])->sort()->values()->all())
        ->and($ids(['search' => 'Bob Example']))->toBe([$pending->id])
        ->and($ids(['search' => 'alice@example.com']))->toBe(collect([$approved->id, $rejected->id])->sort()->values()->all())
        ->and($ids(['search' => $approved->provider_transaction_id]))->toBe([$approved->id]);
});

test('the activity table carries every column an administrator needs', function () {
    $user = User::factory()->create(['name' => 'Alice Example']);
    AdActivity::factory()->for($user)->approved(1_320_000, 25)->create(['offer_identifier' => '555001']);

    $this->actingAs($this->admin)->get(route('admin.ads.activity'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ads/activity')
            ->has('activities.data', 1)
            ->where('activities.data.0.user.name', 'Alice Example')
            ->where('activities.data.0.survey', '555001')
            ->where('activities.data.0.status', 'approved')
            ->where('activities.data.0.publisher_revenue_usd', '1.32')
            ->where('activities.data.0.points', 25)
            ->where('activities.data.0.reward_status', 'credited')
            ->has('activities.data.0.started_at')
            ->has('activities.data.0.confirmed_at')
            ->where('activities.per_page', 25));
});

test('the activity log is paginated', function () {
    AdActivity::factory()->count(30)->create();

    $this->actingAs($this->admin)->get(route('admin.ads.activity'))
        ->assertInertia(fn ($page) => $page->has('activities.data', 25)->where('activities.total', 30)->where('activities.last_page', 2));

    $this->actingAs($this->admin)->get(route('admin.ads.activity', ['page' => 2]))
        ->assertInertia(fn ($page) => $page->has('activities.data', 5));
});

test('invalid filter values are rejected instead of being passed to the query', function () {
    $this->actingAs($this->admin)->get(route('admin.ads.activity', ['status' => 'bogus']))->assertSessionHasErrors('status');
    $this->actingAs($this->admin)->get(route('admin.ads.activity', ['from' => 'not-a-date']))->assertSessionHasErrors('from');
});

test('one transaction shows its whole story: timeline, callbacks, identifiers, revenue and reward', function () {
    $user = User::factory()->create();
    ProviderIdentity::query()->create(['user_id' => $user->id, 'provider_key' => 'tapresearch', 'external_user_id' => 'uexample']);
    AdRewardRule::query()->create(['provider_key' => null, 'activity_type' => 'survey', 'points' => 25, 'is_enabled' => true]);

    $activity = AdActivity::factory()->for($user)->create([
        'provider_user_id' => 'uexample', 'offer_identifier' => '555001', 'survey_returned_at' => now(), 'status' => ActivityStatus::Returned,
    ]);

    $this->getJson(TapResearchFixtures::postbackUrl(['activity_id' => $activity->id, 'uid' => 'uexample', 'offer_identifier' => '555001', 'cpid' => 'cpid-story-1', 'revenue' => '1.32']))->assertOk();

    $this->actingAs($this->admin)->get(route('admin.ads.activity.show', $activity))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ads/activity-show')
            ->where('activity.provider_transaction_id', 'cpid-story-1')
            ->where('activity.publisher_revenue_usd', '1.32')
            ->where('activity.awarded_points', 25)
            ->where('activity.callback_verification_status', 'verified')
            ->where('timeline', fn ($timeline) => collect($timeline)->pluck('state', 'key')->all() === [
                'loaded' => 'done',
                'started' => 'done',
                'returned' => 'done',
                'postback' => 'done',
                'signature' => 'done',
                'confirmed' => 'done',
                'calculated' => 'done',
                'credited' => 'done',
            ])
            ->has('callbacks', 1)
            ->where('callbacks.0.result', 'credited')
            ->where('callbacks.0.signature_valid', true)
            ->where('callbacks.0.payload.cpid', 'cpid-story-1')
            ->missing('callbacks.0.payload.sig')
            ->has('ledger', 1)
            ->where('ledger.0.key', 'reward:tapresearch:cpid-story-1')
            ->where('ledger.0.points', 25));
});

test('a survey still waiting for the provider shows its remaining steps as pending, not done', function () {
    $activity = AdActivity::factory()->create(['survey_returned_at' => now(), 'status' => ActivityStatus::Returned]);

    $this->actingAs($this->admin)->get(route('admin.ads.activity.show', $activity))
        ->assertInertia(fn ($page) => $page
            ->where('timeline', fn ($timeline) => collect($timeline)->pluck('state', 'key')->only(['returned', 'postback', 'confirmed', 'credited'])->all() === [
                'returned' => 'done',
                'postback' => 'pending',
                'confirmed' => 'pending',
                'credited' => 'pending',
            ])
            ->has('callbacks', 0));
});

test('a rejected survey is explained in the timeline and a postback-only payout is labelled as such', function () {
    $user = User::factory()->create();
    ProviderIdentity::query()->create(['user_id' => $user->id, 'provider_key' => 'tapresearch', 'external_user_id' => 'uexample']);
    $activity = AdActivity::factory()->for($user)->create(['provider_user_id' => 'uexample']);

    $this->getJson(TapResearchFixtures::postbackUrl(['activity_id' => $activity->id, 'uid' => 'uexample', 'survey_outcome' => 2, 'cpid' => 'cpid-dq-9']))->assertOk();
    $this->getJson(TapResearchFixtures::postbackUrl(['uid' => 'stranger', 'cpid' => 'cpid-orphan-9']))->assertOk();

    $this->actingAs($this->admin)->get(route('admin.ads.activity.show', $activity))
        ->assertInertia(fn ($page) => $page
            ->where('activity.status', 'rejected')
            ->where('timeline', fn ($timeline) => collect($timeline)->firstWhere('key', 'confirmed')['state'] === 'skipped'));

    $orphan = AdActivity::query()->where('provider_transaction_id', 'cpid-orphan-9')->firstOrFail();
    $this->actingAs($this->admin)->get(route('admin.ads.activity.show', $orphan))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activity.source', 'postback_only')
            ->where('timeline.0.key', 'unmatched')
            ->where('provider_calls', []));
});

// ---------------------------------------------------------------- providers

test('provider credentials are never sent to the browser', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.ads.providers'))->assertOk();

    $response->assertDontSee('test-api-secret')->assertDontSee('test-api-token')->assertDontSee('test-reporting-token');
    $response->assertInertia(fn ($page) => $page
        ->where('providers.0.configured', true)
        ->where('providers.0.hints.api_token', '••••oken')
        ->where('providers.0.hints.api_secret', '••••cret')
        ->missing('providers.0.settings'));
});

test('the page offers the callback and return URLs to give the provider', function () {
    $this->actingAs($this->admin)->get(route('admin.ads.providers'))
        ->assertInertia(fn ($page) => $page
            ->where('providers.0.callback_url', url('/api/webhooks/tapresearch/reward'))
            ->where('providers.0.return_url', url('/surveys/return/tapresearch')));
});

test('registered providers get a disabled row automatically', function () {
    AdProvider::query()->forceDelete();

    $this->actingAs($this->admin)->get(route('admin.ads.providers'))
        ->assertInertia(fn ($page) => $page
            ->has('providers', 2)
            ->where('providers.0.key', 'tapresearch')
            ->where('providers.0.is_enabled', false)
            ->where('providers.0.configured', false)
            // The AdSense placeholder is listed last, disabled, and can never be configured yet.
            ->where('providers.1.key', 'google_adsense')
            ->where('providers.1.is_enabled', false)
            ->where('providers.1.configured', false));
});

test('credentials are write-only: a blank field keeps the stored value, a filled one replaces it', function () {
    $this->actingAs($this->admin)->put(route('admin.ads.providers.update', $this->provider), [
        'api_token' => '', 'api_secret' => 'brand-new-secret', 'reporting_access_token' => '',
    ])->assertRedirect();

    $settings = $this->provider->fresh()->settings;
    expect($settings['api_token'])->toBe('test-api-token')
        ->and($settings['api_secret'])->toBe('brand-new-secret')
        ->and($settings['reporting_access_token'])->toBe('test-reporting-token');

    // They are encrypted at rest.
    expect(DB::table('ad_providers')->where('id', $this->provider->id)->value('settings'))->not->toContain('brand-new-secret');

    $audit = AdProviderAudit::query()->latest('id')->first();
    expect($audit->action)->toBe('updated')->and($audit->meta['changed'])->toBe(['api_secret'])
        ->and(json_encode($audit->meta))->not->toContain('brand-new-secret');
});

test('a provider cannot be enabled until it has credentials, and can always be disabled', function () {
    $this->provider->update(['settings' => null, 'is_enabled' => false]);

    $this->actingAs($this->admin)->put(route('admin.ads.providers.update', $this->provider), ['is_enabled' => true])
        ->assertSessionHasErrors('is_enabled');
    expect($this->provider->fresh()->is_enabled)->toBeFalse();

    $this->actingAs($this->admin)->put(route('admin.ads.providers.update', $this->provider), ['is_enabled' => true, 'api_token' => 't', 'api_secret' => 's'])
        ->assertSessionHasNoErrors();
    expect($this->provider->fresh()->is_enabled)->toBeTrue();

    $this->actingAs($this->admin)->put(route('admin.ads.providers.update', $this->provider), ['is_enabled' => false])->assertSessionHasNoErrors();
    expect($this->provider->fresh()->is_enabled)->toBeFalse();
});

test('priority can be set and providers can be moved in the fallback order', function () {
    $second = AdProvider::factory()->create(['key' => 'alpha', 'label' => 'Alpha', 'priority' => 20]);
    $third = AdProvider::factory()->create(['key' => 'beta', 'label' => 'Beta', 'priority' => 30]);

    $this->actingAs($this->admin)->post(route('admin.ads.providers.move', $third), ['direction' => 'up'])->assertRedirect();

    expect(AdProvider::query()->orderBy('priority')->pluck('key')->all())->toBe(['tapresearch', 'beta', 'alpha']);

    $this->actingAs($this->admin)->post(route('admin.ads.providers.move', $this->provider), ['direction' => 'down']);
    expect(AdProvider::query()->orderBy('priority')->pluck('key')->all())->toBe(['beta', 'tapresearch', 'alpha']);

    // Moving the first one up (or last one down) is a harmless no-op.
    $this->actingAs($this->admin)->post(route('admin.ads.providers.move', $third), ['direction' => 'up']);
    expect(AdProvider::query()->orderBy('priority')->pluck('key')->all())->toBe(['beta', 'tapresearch', 'alpha']);

    $this->actingAs($this->admin)->put(route('admin.ads.providers.update', $second), ['priority' => 1]);
    expect(AdProvider::query()->orderBy('priority')->first()->key)->toBe('alpha');

    $this->actingAs($this->admin)->put(route('admin.ads.providers.update', $second), ['priority' => 0])->assertSessionHasErrors('priority');
});

test('the fallback order preview lists only enabled providers in priority order', function () {
    AdProvider::factory()->create(['key' => 'alpha', 'label' => 'Alpha', 'priority' => 5]);
    AdProvider::factory()->disabled()->create(['key' => 'beta', 'label' => 'Beta', 'priority' => 1]);

    $this->actingAs($this->admin)->get(route('admin.ads.providers'))
        ->assertInertia(fn ($page) => $page->where('fallback_order', fn ($order) => collect($order)->pluck('key')->all() === ['alpha', 'tapresearch']));
});

test('testing a connection records the result and the provider health', function () {
    Http::fake(['*/campaigns*' => Http::response(TapResearchFixtures::campaigns([], false, 2))]);

    $this->actingAs($this->admin)->post(route('admin.ads.providers.test', $this->provider))->assertRedirect();

    $fresh = $this->provider->fresh();
    expect($fresh->health)->toBe('healthy')->and($fresh->last_tested_at)->not->toBeNull();
    expect(AdProviderCall::query()->where('operation', 'test_connection')->value('outcome')->value)->toBe('success');

    Http::fake(['*/campaigns*' => Http::response([], 401)]);
});

test('a failing connection test marks the provider unhealthy', function () {
    Http::fake(['*/campaigns*' => Http::response([], 401)]);

    $this->actingAs($this->admin)->post(route('admin.ads.providers.test', $this->provider))->assertRedirect();

    expect($this->provider->fresh()->health)->toBe('unhealthy')
        ->and($this->provider->fresh()->last_error_category)->toBe('invalid_credentials');
});

// ---------------------------------------------------------------- rewards

test('administrators manage reward rules: global, provider-specific, enable and disable', function () {
    $this->actingAs($this->admin)->post(route('admin.ads.rewards.store'), ['provider_key' => null, 'activity_type' => 'survey', 'points' => 20])->assertSessionHasNoErrors();
    $this->actingAs($this->admin)->post(route('admin.ads.rewards.store'), ['provider_key' => 'tapresearch', 'activity_type' => 'survey', 'points' => 35])->assertSessionHasNoErrors();

    expect(AdRewardRule::query()->count())->toBe(2);

    // Saving the same pair again replaces rather than duplicates.
    $this->actingAs($this->admin)->post(route('admin.ads.rewards.store'), ['provider_key' => 'tapresearch', 'activity_type' => 'survey', 'points' => 40]);
    expect(AdRewardRule::query()->count())->toBe(2)
        ->and(AdRewardRule::query()->where('provider_key', 'tapresearch')->value('points'))->toBe(40);

    $global = AdRewardRule::query()->whereNull('provider_key')->firstOrFail();
    $this->actingAs($this->admin)->put(route('admin.ads.rewards.update', $global), ['points' => 22, 'is_enabled' => false]);
    expect($global->fresh()->points)->toBe(22)->and($global->fresh()->is_enabled)->toBeFalse();

    $this->actingAs($this->admin)->delete(route('admin.ads.rewards.destroy', $global));
    expect(AdRewardRule::query()->count())->toBe(1);

    $this->actingAs($this->admin)->get(route('admin.ads.rewards'))
        ->assertInertia(fn ($page) => $page->component('admin/ads/rewards')->has('rules', 1)->where('rules.0.provider_key', 'tapresearch'));
});

test('reward rules validate their input', function () {
    $this->actingAs($this->admin)->post(route('admin.ads.rewards.store'), ['activity_type' => 'survey', 'points' => -1])->assertSessionHasErrors('points');
    $this->actingAs($this->admin)->post(route('admin.ads.rewards.store'), ['activity_type' => 'nonsense', 'points' => 5])->assertSessionHasErrors('activity_type');
    $this->actingAs($this->admin)->post(route('admin.ads.rewards.store'), ['provider_key' => 'ghost', 'activity_type' => 'survey', 'points' => 5])->assertSessionHasErrors('provider_key');
});

test('settings are stored as integers and the timezone must be real', function () {
    $valid = [
        'settlement_timezone' => 'America/Chicago',
        'point_usd_value' => '0.004',
        'reconciliation_tolerance_usd' => '0.02',
        'reconciliation_recheck_days' => 5,
        'offers_cache_seconds' => 90,
    ];

    $this->actingAs($this->admin)->put(route('admin.ads.rewards.settings'), $valid)->assertSessionHasNoErrors();

    $settings = AdSetting::current();
    expect($settings->settlement_timezone)->toBe('America/Chicago')
        ->and($settings->point_usd_value_micros)->toBe(4_000)
        ->and($settings->reconciliation_tolerance_micros)->toBe(20_000)
        ->and($settings->reconciliation_recheck_days)->toBe(5)
        ->and($settings->offers_cache_seconds)->toBe(90);

    $this->actingAs($this->admin)->put(route('admin.ads.rewards.settings'), [...$valid, 'settlement_timezone' => 'Mars/Olympus'])->assertSessionHasErrors('settlement_timezone');
    $this->actingAs($this->admin)->put(route('admin.ads.rewards.settings'), [...$valid, 'point_usd_value' => 'lots'])->assertSessionHasErrors('point_usd_value');

    // Clearing the point value turns the estimate off again.
    $this->actingAs($this->admin)->put(route('admin.ads.rewards.settings'), [...$valid, 'point_usd_value' => ''])->assertSessionHasNoErrors();
    expect(AdSetting::current()->point_usd_value_micros)->toBeNull();
});

// ---------------------------------------------------------------- monitoring

test('the provider request log shows outcomes and a success rate per provider', function () {
    Http::fake(['*/campaigns*' => Http::sequence()->push(TapResearchFixtures::campaigns([TapResearchFixtures::survey()]))->push([], 503)]);
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('surveys.offers'))->assertOk();
    $this->actingAs($user)->postJson(route('surveys.offers'))->assertOk();

    $this->actingAs($this->admin)->get(route('admin.ads.requests'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ads/requests')
            ->has('calls.data', 2)
            ->where('stats.0.provider', 'tapresearch')
            ->where('stats.0.total', 2)
            ->where('stats.0.success', 1)
            ->where('stats.0.failed', 1));

    $this->actingAs($this->admin)->get(route('admin.ads.requests', ['outcome' => 'failed']))
        ->assertInertia(fn ($page) => $page->has('calls.data', 1)->where('calls.data.0.error', 'provider_unavailable'));
});

test('the callback log lists valid, invalid and duplicate callbacks', function () {
    $this->getJson(TapResearchFixtures::postbackUrl(['cpid' => 'cpid-log-1']))->assertOk();
    $this->getJson(TapResearchFixtures::postbackUrl(['cpid' => 'cpid-log-1']))->assertOk();
    $this->getJson('/api/webhooks/tapresearch/reward?cpid=x&sig=bad')->assertUnauthorized();

    expect(ProviderCallback::query()->count())->toBe(3);

    $this->actingAs($this->admin)->get(route('admin.ads.callbacks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/ads/callbacks')->has('callbacks.data', 3));

    $this->actingAs($this->admin)->get(route('admin.ads.callbacks', ['result' => 'invalid_signature']))
        ->assertInertia(fn ($page) => $page->has('callbacks.data', 1)->where('callbacks.data.0.signature_valid', false)->where('callbacks.data.0.revenue_usd', '0.00'));
});

// ---------------------------------------------------------------- reconciliation pages

test('the reconciliation page shows each day with provider-reported, local and difference', function () {
    AdReconciliation::query()->create([
        'provider_key' => 'tapresearch', 'report_date' => '2026-11-10', 'timezone' => 'America/New_York',
        'provider_revenue_micros' => 3_000_000, 'local_revenue_micros' => 2_500_000, 'difference_micros' => 500_000,
        'local_transaction_count' => 4, 'status' => ReconciliationStatus::Discrepancy, 'fetched_at' => now(),
    ]);

    $this->actingAs($this->admin)->get(route('admin.ads.reconciliation'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ads/reconciliation')
            ->where('reconciliations.data.0.date', '2026-11-10')
            ->where('reconciliations.data.0.provider_revenue_usd', '3.00')
            ->where('reconciliations.data.0.local_revenue_usd', '2.50')
            ->where('reconciliations.data.0.difference_usd', '0.50')
            ->where('reconciliations.data.0.status', 'discrepancy')
            ->where('reconciliations.data.0.local_transaction_count', 4));
});

test('an administrator can re-run a day and the lines are shown on its page', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-11-12 12:00:00', 'America/New_York'));
    AdActivity::factory()->approved(1_000_000, 25)->create(['provider_user_id' => 'uone', 'provider_confirmed_at' => CarbonImmutable::parse('2026-11-10 12:00:00', 'America/New_York')->utc()]);
    Http::fake(['*suppliers/daily_stats*' => Http::response([
        ['date' => '2026-11-10', 'country_abbr' => 'US', 'revenue' => '1.00', 'user_identifier' => 'uone'],
        ['date' => '2026-11-10', 'country_abbr' => 'CA', 'revenue' => '0.40', 'user_identifier' => 'umissing'],
    ])]);

    $this->actingAs($this->admin)->post(route('admin.ads.reconciliation.run'), ['provider' => 'tapresearch', 'date' => '2026-11-10'])->assertSessionHasNoErrors();
    $this->actingAs($this->admin)->post(route('admin.ads.reconciliation.run'), ['provider' => 'tapresearch', 'date' => '2026-11-10'])->assertSessionHasNoErrors();

    expect(AdReconciliation::query()->count())->toBe(1);

    $record = AdReconciliation::query()->firstOrFail();
    $this->actingAs($this->admin)->get(route('admin.ads.reconciliation.show', $record))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ads/reconciliation-show')
            ->where('reconciliation.status', 'discrepancy')
            ->where('reconciliation.difference_usd', '0.40')
            ->has('lines', 2)
            ->where('lines.0.flag', 'missing_local')
            ->has('country_breakdown', 2));

    $this->actingAs($this->admin)->post(route('admin.ads.reconciliation.run'), ['provider' => 'tapresearch', 'date' => 'tomorrow'])->assertSessionHasErrors('date');
});

test('settled revenue is recorded by hand and validated', function () {
    $payload = ['provider_key' => 'tapresearch', 'period_start' => '2026-11-01', 'period_end' => '2026-11-07', 'amount_usd' => '125.50', 'reference' => 'Wire 42', 'received_at' => '2026-11-10'];

    $this->actingAs($this->admin)->post(route('admin.ads.reconciliation.payouts'), $payload)->assertSessionHasNoErrors();

    $payout = AdProviderPayout::query()->sole();
    expect($payout->amount_micros)->toBe(125_500_000)->and($payout->recorded_by)->toBe($this->admin->id);

    $this->actingAs($this->admin)->post(route('admin.ads.reconciliation.payouts'), [...$payload, 'amount_usd' => 'a lot'])->assertSessionHasErrors('amount_usd');
    $this->actingAs($this->admin)->post(route('admin.ads.reconciliation.payouts'), [...$payload, 'period_end' => '2026-10-01'])->assertSessionHasErrors('period_end');

    $this->actingAs($this->admin)->get(route('admin.ads.reconciliation'))
        ->assertInertia(fn ($page) => $page->has('payouts', 1)->where('payouts.0.amount_usd', '125.50'));
});
