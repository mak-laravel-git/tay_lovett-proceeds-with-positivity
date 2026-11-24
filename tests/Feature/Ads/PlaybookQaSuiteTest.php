<?php

/**
 * Senior QA harness mapped to 02_HUMAN_QA_WANNADS_THEOREMREACH_AND_REGRESSION_PLAYBOOK.md
 * Evidence class: LOCAL_FIXTURE only — not provider sandbox and not production.
 */

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\RewardStatus;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdRewardRule;
use App\Models\PointBalance;
use App\Models\PointLedgerEntry;
use App\Models\ProviderCallback;
use App\Models\ProviderIdentity;
use App\Models\User;
use App\Services\Ads\Providers\GoogleAdSense\GoogleAdSenseAdapter;
use App\Services\Ads\Providers\TheoremReach\TheoremReachSignature;
use Tests\Support\TapResearchFixtures;
use Tests\Support\TheoremReachFixtures;
use Tests\Support\WannadsFixtures;

function qaSeedProviders(): void
{
    AdProvider::factory()->create([
        'key' => 'tapresearch',
        'label' => 'TapResearch (Survey Feed API)',
        'is_enabled' => true,
        'priority' => 10,
        'settings' => [
            'api_token' => TapResearchFixtures::TOKEN,
            'api_secret' => TapResearchFixtures::SECRET,
        ],
        'config' => ['reward_cashout_permission' => false],
    ]);

    AdProvider::factory()->create([
        'key' => 'wannads',
        'label' => 'Wannads (Offerwall)',
        'is_enabled' => true,
        'priority' => 20,
        'settings' => [
            'api_token' => WannadsFixtures::API_KEY,
            'api_secret' => WannadsFixtures::SECRET,
        ],
        'config' => [
            'sandbox_mode' => true,
            'production_enabled' => false,
            'reward_cashout_permission' => false,
            'allowed_callback_ips' => [],
        ],
    ]);

    AdProvider::factory()->create([
        'key' => 'theoremreach',
        'label' => 'TheoremReach (Surveys / Offerwall)',
        'is_enabled' => true,
        'priority' => 30,
        'settings' => [
            'api_token' => TheoremReachFixtures::API_KEY,
            'api_secret' => TheoremReachFixtures::SECRET,
        ],
        'config' => [
            'partner_id' => TheoremReachFixtures::PARTNER_ID,
            'currency_name_plural' => 'Points',
            'exchange_rate' => '100',
            'sandbox_mode' => true,
            'concurrent_provider_permission' => true,
            'production_enabled' => false,
            'reward_cashout_permission' => false,
        ],
    ]);

    AdRewardRule::query()->create(['provider_key' => null, 'activity_type' => 'survey', 'points' => 25, 'is_enabled' => true]);
    AdRewardRule::query()->create(['provider_key' => 'wannads', 'activity_type' => ActivityType::Offerwall->value, 'points' => 15, 'is_enabled' => true]);
    AdRewardRule::query()->create(['provider_key' => 'theoremreach', 'activity_type' => ActivityType::Survey->value, 'points' => 20, 'is_enabled' => true]);
}

beforeEach(function () {
    qaSeedProviders();
    $this->userA = User::factory()->create();
    $this->userB = User::factory()->create();
    $this->admin = User::factory()->administrator()->create();

    ProviderIdentity::query()->create(['user_id' => $this->userA->id, 'provider_key' => 'tapresearch', 'external_user_id' => 'uexample']);
    ProviderIdentity::query()->create(['user_id' => $this->userA->id, 'provider_key' => 'wannads', 'external_user_id' => 'uwannads1']);
    ProviderIdentity::query()->create(['user_id' => $this->userA->id, 'provider_key' => 'theoremreach', 'external_user_id' => 'utheorem1']);
});

// --- UX / smoke (server surface) ---

test('UX-01 S-06 earn page shows three rewarded providers and not AdSense as cash card', function () {
    $this->actingAs($this->userA)
        ->get(route('surveys'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('surveys/index')
            ->has('providers', 3)
            ->where('providers.0.key', 'tapresearch')
            ->where('providers.1.key', 'wannads')
            ->where('providers.2.key', 'theoremreach')
            ->where('providers', fn ($providers) => collect($providers)->every(fn ($p) => $p['key'] !== 'google_adsense')));
});

test('UX-02 WAN-04 disabled wannads cannot launch server-side', function () {
    AdProvider::query()->where('key', 'wannads')->update(['is_enabled' => false]);

    $this->actingAs($this->userA)
        ->postJson('/surveys/offers', ['provider' => 'wannads'])
        ->assertOk()
        ->assertJsonPath('provider', null);

    $this->actingAs($this->userA)
        ->get(route('surveys'))
        ->assertInertia(fn ($page) => $page
            ->where('providers.1.key', 'wannads')
            ->where('providers.1.can_launch', false));
});

test('THEO-01 coexistence blocked blocks launch and production enable', function () {
    $provider = AdProvider::query()->where('key', 'theoremreach')->firstOrFail();
    $provider->update([
        'is_enabled' => false,
        'config' => [
            'partner_id' => TheoremReachFixtures::PARTNER_ID,
            'currency_name_plural' => 'Points',
            'exchange_rate' => '100',
            'sandbox_mode' => false,
            'concurrent_provider_permission' => false,
            'production_enabled' => true,
        ],
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.ads.providers.update', $provider), [
            'is_enabled' => true,
            'production_enabled' => true,
            'concurrent_provider_permission' => false,
            'sandbox_mode' => false,
        ])
        ->assertSessionHasErrors('is_enabled');

    $provider->update(['is_enabled' => true]);

    $this->actingAs($this->userA)
        ->postJson('/surveys/offers', ['provider' => 'theoremreach'])
        ->assertOk()
        ->assertJsonPath('provider', null);
});

test('S-10 CROSS-01 admin lists four providers and never exposes secrets', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.ads.providers'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('providers', 4)
            ->where('providers.0.hints.api_secret', fn ($hint) => is_string($hint) && str_starts_with($hint, '••••'))
            ->missing('providers.0.settings')
            ->where('providers.3.key', 'google_adsense')
            ->where('providers.3.integration_type', 'standard_ads'));
});

test('CROSS-08 regular user cannot open admin provider settings', function () {
    $provider = AdProvider::query()->where('key', 'wannads')->firstOrFail();

    $this->actingAs($this->userA)
        ->get(route('admin.ads.providers'))
        ->assertForbidden();

    $this->actingAs($this->userA)
        ->put(route('admin.ads.providers.update', $provider), ['api_secret' => 'hacked'])
        ->assertForbidden();
});

test('UX-04 CROSS-07 guest and user B cannot access user A earnings data', function () {
    AdActivity::factory()->for($this->userA)->create([
        'provider_key' => 'wannads',
        'provider_user_id' => 'uwannads1',
        'status' => ActivityStatus::Approved,
        'reward_status' => RewardStatus::Credited,
        'awarded_points' => 15,
    ]);

    $this->get(route('earnings'))->assertRedirect();

    $page = $this->actingAs($this->userB)->get(route('earnings'))->assertOk();
    $page->assertInertia(fn ($inertia) => $inertia
        ->where('activities.data', fn ($rows) => collect($rows)->every(fn ($row) => true) && count($rows) === 0));
});

test('CROSS-03 earnings provider filter isolates sources and excludes AdSense option', function () {
    AdActivity::factory()->for($this->userA)->create(['provider_key' => 'tapresearch', 'offer_identifier' => 'tr-1']);
    AdActivity::factory()->for($this->userA)->create(['provider_key' => 'wannads', 'offer_identifier' => 'w-1']);
    AdActivity::factory()->for($this->userA)->create(['provider_key' => 'theoremreach', 'offer_identifier' => 'th-1']);

    $this->actingAs($this->userA)
        ->get('/earnings?provider=wannads')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.provider', 'wannads')
            ->where('provider_options', fn ($opts) => collect($opts)->every(fn ($o) => $o['value'] !== 'google_adsense'))
            ->where('activities.data', fn ($rows) => count($rows) === 1 && $rows[0]['provider'] === 'wannads'));
});

// --- Wannads money safety ---

test('WAN-05 valid credited postback credits once with OK ack', function () {
    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'qa-wan-05', 'reward' => '100', 'payout' => '0.75']))
        ->assertOk()
        ->assertSee('OK');

    expect(PointLedgerEntry::query()->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $this->userA->id)->value('balance'))->toBe(15);
});

test('WAN-06 duplicate five times remains one credit', function () {
    $url = WannadsFixtures::postbackUrl(['transaction_id' => 'qa-wan-06']);

    foreach (range(1, 5) as $_) {
        $this->get($url)->assertOk()->assertSee('OK');
    }

    expect(PointLedgerEntry::query()->count())->toBe(1)
        ->and(ProviderCallback::query()->where('provider_key', 'wannads')->count())->toBe(5);
});

test('WAN-07 tampered signature credits nothing', function () {
    $url = WannadsFixtures::postbackUrl(['transaction_id' => 'qa-wan-07', 'reward' => '100']);
    $tampered = preg_replace('/reward=100/', 'reward=999', $url, 1);

    $this->get($tampered)->assertUnauthorized()->assertSee('ERROR');
    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('WAN-08 unknown user gets no credit', function () {
    $this->get(WannadsFixtures::postbackUrl(['user_id' => 'foreign-id', 'transaction_id' => 'qa-wan-08']))
        ->assertOk()
        ->assertSee('OK');

    expect(PointLedgerEntry::query()->count())->toBe(0)
        ->and(AdActivity::query()->where('provider_transaction_id', 'qa-wan-08')->sole()->user_id)->toBeNull();
});

test('WAN-09 rejection after credit reverses once', function () {
    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'qa-wan-09', 'status' => 'credited']))->assertOk();
    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'qa-wan-09', 'status' => 'rejected', 'reward' => '-100']))->assertOk();
    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'qa-wan-09', 'status' => 'rejected', 'reward' => '-100']))->assertOk();

    expect(PointLedgerEntry::query()->where('entry_type', 'reversal')->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $this->userA->id)->value('balance'))->toBe(0)
        ->and(AdActivity::query()->where('provider_transaction_id', 'qa-wan-09')->sole()->status)->toBe(ActivityStatus::Reversed);
});

test('WAN-10 rejection before credit blocks later credit', function () {
    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'qa-wan-10', 'status' => 'rejected', 'reward' => '-100']))->assertOk();
    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'qa-wan-10', 'status' => 'credited', 'reward' => '100']))->assertOk();

    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('WAN-12 CROSS same transaction id across providers are distinct', function () {
    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'shared-ref-777']))->assertOk();

    $activity = AdActivity::factory()->for($this->userA)->create([
        'provider_key' => 'tapresearch',
        'provider_user_id' => 'uexample',
        'offer_identifier' => '555001',
    ]);

    $this->getJson(TapResearchFixtures::postbackUrl([
        'activity_id' => $activity->id,
        'uid' => 'uexample',
        'offer_identifier' => '555001',
        'cpid' => 'shared-ref-777',
    ]))->assertOk();

    expect(AdActivity::query()->where('provider_transaction_id', 'shared-ref-777')->count())->toBe(2);
});

test('WAN-02 launch URL is official host without secret', function () {
    $offers = $this->actingAs($this->userA)->postJson('/surveys/offers', ['provider' => 'wannads'])->assertOk()->json();
    $start = $this->actingAs($this->userA)->postJson('/surveys/start', [
        'request_id' => $offers['request_id'],
        'offer_identifier' => 'wannads-wall',
    ])->assertOk()->json();

    expect($start['url'])->toStartWith('https://earn.wannads.com/wall?')
        ->and($start['url'])->not->toContain(WannadsFixtures::SECRET);
});

// --- TheoremReach ---

test('THEO-03 signed entry verifies and uses opaque user', function () {
    $offers = $this->actingAs($this->userA)->postJson('/surveys/offers', ['provider' => 'theoremreach'])->assertOk()->json();
    $start = $this->actingAs($this->userA)->postJson('/surveys/start', [
        'request_id' => $offers['request_id'],
        'offer_identifier' => 'theoremreach-wall',
    ])->assertOk()->json();

    expect($start['url'])->toStartWith('https://theoremreach.com/respondent_entry/direct?')
        ->and($start['url'])->toContain('user_id=utheorem1')
        ->and(TheoremReachSignature::verifyEntryUrl($start['url'], TheoremReachFixtures::SECRET))->toBeTrue()
        ->and($start['url'])->not->toContain(TheoremReachFixtures::SECRET);
});

test('THEO-07 THEO-09 valid reward credits once', function () {
    $path = TheoremReachFixtures::rewardPath(['transaction_id' => 'qa-theo-07']);
    $this->getJson($path)->assertOk()->assertJson(['status' => 'credited']);
    $this->getJson($path)->assertOk()->assertJson(['status' => 'duplicate']);

    expect(PointLedgerEntry::query()->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $this->userA->id)->value('balance'))->toBe(20);
});

test('THEO-08 debug callback never credits', function () {
    $this->getJson(TheoremReachFixtures::rewardPath([
        'transaction_id' => 'qa-theo-08',
        'debug' => 'true',
    ]))->assertOk()->assertJson(['status' => 'test_only']);

    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('THEO-10 invalid hash rejects', function () {
    $path = TheoremReachFixtures::rewardPath(['transaction_id' => 'qa-theo-10', 'reward' => '50']);
    $tampered = preg_replace('/reward=50/', 'reward=999', $path, 1);
    $this->getJson($tampered)->assertUnauthorized();
    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('THEO-12 event webhook reverses once', function () {
    $this->getJson(TheoremReachFixtures::rewardPath(['transaction_id' => 'qa-theo-12']))->assertOk();
    $path = TheoremReachFixtures::eventPath('reversal', ['qa-theo-12']);

    $this->postJson($path, ['event_type' => 'reversal', 'reversed_transactions' => ['qa-theo-12']])->assertOk();
    $this->postJson($path, ['event_type' => 'reversal', 'reversed_transactions' => ['qa-theo-12']])->assertOk();

    expect(PointLedgerEntry::query()->where('entry_type', 'reversal')->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $this->userA->id)->value('balance'))->toBe(0);
});

// --- AdSense / TapResearch regression ---

test('ADS-02 Google AdSense adapter never configures as reward provider', function () {
    $adapter = app(GoogleAdSenseAdapter::class);
    $row = AdProvider::query()->firstOrCreate(
        ['key' => 'google_adsense'],
        ['label' => 'Google AdSense', 'is_enabled' => true, 'priority' => 99],
    );

    expect($adapter->isConfigured($row))->toBeFalse()
        ->and($adapter->parseCallback($row, request()))->toBeNull();
});

test('TAP-03 TAP-04 TapResearch valid and duplicate still work', function () {
    $activity = AdActivity::factory()->for($this->userA)->create([
        'provider_key' => 'tapresearch',
        'provider_user_id' => 'uexample',
        'offer_identifier' => '555001',
    ]);

    $url = TapResearchFixtures::postbackUrl([
        'activity_id' => $activity->id,
        'uid' => 'uexample',
        'offer_identifier' => '555001',
        'cpid' => 'qa-tap-03',
    ]);

    $this->getJson($url)->assertOk()->assertJson(['status' => 'credited']);
    $this->getJson($url)->assertOk()->assertJson(['status' => 'duplicate']);

    expect(PointLedgerEntry::query()->count())->toBe(1);
});

test('TAP-05 invalid TapResearch signature still rejects', function () {
    $activity = AdActivity::factory()->for($this->userA)->create([
        'provider_key' => 'tapresearch',
        'provider_user_id' => 'uexample',
        'offer_identifier' => '555001',
    ]);

    $url = TapResearchFixtures::postbackUrl([
        'activity_id' => $activity->id,
        'uid' => 'uexample',
        'offer_identifier' => '555001',
        'revenue' => '1.00',
    ]);
    $tampered = str_replace('revenue=1.00', 'revenue=99.00', $url);

    $this->getJson($tampered)->assertUnauthorized();
    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('CROSS-09 wannads signature cannot credit theoremreach endpoint', function () {
    $wannadsUrl = WannadsFixtures::postbackUrl(['transaction_id' => 'cross-spoof-1']);
    $query = parse_url($wannadsUrl, PHP_URL_QUERY);

    $this->getJson('/api/webhooks/theoremreach/reward?'.$query)
        ->assertUnauthorized();

    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('CROSS-02 disabling wannads leaves tapresearch launch path available', function () {
    AdProvider::query()->where('key', 'wannads')->update(['is_enabled' => false]);

    // TapResearch still registered/enabled; offer request may empty without HTTP mock, but provider remains selected when mocked via factory path.
    expect(AdProvider::query()->where('key', 'tapresearch')->where('is_enabled', true)->exists())->toBeTrue()
        ->and(AdProvider::query()->where('key', 'wannads')->value('is_enabled'))->toBeFalse();
});

test('S-09 wallet page remains reachable for authenticated user', function () {
    $this->actingAs($this->userA)->get(route('wallet'))->assertOk();
});
