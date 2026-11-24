<?php

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\RewardStatus;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdRewardRule;
use App\Models\PointBalance;
use App\Models\PointLedgerEntry;
use App\Models\ProviderIdentity;
use App\Models\User;
use App\Services\Ads\Providers\TheoremReach\TheoremReachSignature;
use Tests\Support\TheoremReachFixtures;
use Tests\Support\WannadsFixtures;

beforeEach(function () {
    AdProvider::factory()->create([
        'key' => 'theoremreach',
        'label' => 'TheoremReach (Surveys / Offerwall)',
        'is_enabled' => true,
        'settings' => [
            'api_token' => TheoremReachFixtures::API_KEY,
            'api_secret' => TheoremReachFixtures::SECRET,
        ],
        'config' => [
            'partner_id' => TheoremReachFixtures::PARTNER_ID,
            'currency_name_plural' => 'Points',
            'exchange_rate' => '100',
            'sandbox_mode' => true,
            'concurrent_provider_permission' => false,
            'production_enabled' => false,
        ],
    ]);

    AdRewardRule::query()->create([
        'provider_key' => 'theoremreach',
        'activity_type' => ActivityType::Survey->value,
        'points' => 20,
        'is_enabled' => true,
    ]);

    $this->user = User::factory()->create();
    ProviderIdentity::query()->create([
        'user_id' => $this->user->id,
        'provider_key' => 'theoremreach',
        'external_user_id' => 'utheorem1',
    ]);
});

test('T-001 production enable without coexistence permission is rejected', function () {
    $provider = AdProvider::query()->where('key', 'theoremreach')->firstOrFail();
    $provider->update(['is_enabled' => false]);

    expect($provider->fresh()->configValue('concurrent_provider_permission'))->toBeFalse();

    $this->actingAs(User::factory()->administrator()->create())
        ->put(route('admin.ads.providers.update', $provider), [
            'is_enabled' => true,
            'production_enabled' => true,
            'concurrent_provider_permission' => false,
            'sandbox_mode' => false,
        ])
        ->assertSessionHasErrors('is_enabled');
});

test('T-001b coexistence blocked prevents user offers launch', function () {
    AdProvider::query()->where('key', 'theoremreach')->update([
        'config' => [
            'partner_id' => TheoremReachFixtures::PARTNER_ID,
            'currency_name_plural' => 'Points',
            'exchange_rate' => '100',
            'sandbox_mode' => false,
            'concurrent_provider_permission' => false,
            'production_enabled' => true,
        ],
    ]);

    $this->actingAs($this->user)
        ->postJson('/surveys/offers', ['provider' => 'theoremreach'])
        ->assertOk()
        ->assertJsonPath('provider', null)
        ->assertJsonPath('offers', []);
});

test('T-005 verified theoremreach reward credits once', function () {
    $path = TheoremReachFixtures::rewardPath(['transaction_id' => 'tr-credit-1']);

    $this->getJson($path)
        ->assertOk()
        ->assertJson(['status' => 'credited']);

    $this->getJson($path)
        ->assertOk()
        ->assertJson(['status' => 'duplicate']);

    expect(PointLedgerEntry::query()->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $this->user->id)->value('balance'))->toBe(20);
});

test('T-006 debug=true never credits the wallet', function () {
    $path = TheoremReachFixtures::rewardPath([
        'transaction_id' => 'tr-debug-1',
        'debug' => 'true',
    ]);

    $this->getJson($path)
        ->assertOk()
        ->assertJson(['status' => 'test_only']);

    expect(PointLedgerEntry::query()->count())->toBe(0)
        ->and(AdActivity::query()->where('provider_transaction_id', 'tr-debug-1')->sole()->callback_verification_status)->toBe('test');
});

test('T-007 invalid hash rejects', function () {
    $path = TheoremReachFixtures::rewardPath(['transaction_id' => 'tr-bad-1']);
    $tampered = preg_replace('/reward=50/', 'reward=999', $path, 1);

    $this->getJson($tampered)->assertUnauthorized()->assertJson(['status' => 'invalid_signature']);

    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('T-002 entry URL is signed with URL-safe Base64 HMAC-SHA1', function () {
    AdProvider::query()->where('key', 'theoremreach')->update([
        'config' => [
            'partner_id' => TheoremReachFixtures::PARTNER_ID,
            'currency_name_plural' => 'Points',
            'exchange_rate' => '100',
            'sandbox_mode' => true,
            'concurrent_provider_permission' => true,
            'production_enabled' => false,
        ],
    ]);

    $offers = $this->actingAs($this->user)
        ->postJson('/surveys/offers', ['provider' => 'theoremreach'])
        ->assertOk()
        ->json();

    expect($offers['provider'])->toBe('theoremreach');

    $start = $this->actingAs($this->user)
        ->postJson('/surveys/start', [
            'request_id' => $offers['request_id'],
            'offer_identifier' => 'theoremreach-wall',
        ])
        ->assertOk()
        ->json();

    expect($start['url'])->toStartWith('https://theoremreach.com/respondent_entry/direct?')
        ->and(TheoremReachSignature::verifyEntryUrl($start['url'], TheoremReachFixtures::SECRET))->toBeTrue()
        ->and($start['url'])->toContain('user_id=utheorem1')
        ->and($start['url'])->not->toContain(TheoremReachFixtures::SECRET);
});

test('T-009 event webhook reverses a credited transaction once', function () {
    $this->getJson(TheoremReachFixtures::rewardPath(['transaction_id' => 'tr-evt-1']))
        ->assertOk()
        ->assertJson(['status' => 'credited']);

    $path = TheoremReachFixtures::eventPath('reversal', ['tr-evt-1']);

    $this->postJson($path, [
        'event_type' => 'reversal',
        'reversed_transactions' => ['tr-evt-1'],
    ])->assertOk()->assertJsonPath('status', 'ok');

    $this->postJson($path, [
        'event_type' => 'reversal',
        'reversed_transactions' => ['tr-evt-1'],
    ])->assertOk();

    expect(PointLedgerEntry::query()->where('entry_type', 'reversal')->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $this->user->id)->value('balance'))->toBe(0)
        ->and(AdActivity::query()->where('provider_transaction_id', 'tr-evt-1')->sole()->status)->toBe(ActivityStatus::Reversed)
        ->and(AdActivity::query()->where('provider_transaction_id', 'tr-evt-1')->sole()->reward_status)->toBe(RewardStatus::Reversed);
});

test('X-001 same external transaction id from wannads and theoremreach are distinct', function () {
    AdProvider::factory()->create([
        'key' => 'wannads',
        'label' => 'Wannads',
        'is_enabled' => true,
        'settings' => [
            'api_token' => WannadsFixtures::API_KEY,
            'api_secret' => WannadsFixtures::SECRET,
        ],
        'config' => ['allowed_callback_ips' => []],
    ]);
    AdRewardRule::query()->create([
        'provider_key' => 'wannads',
        'activity_type' => ActivityType::Offerwall->value,
        'points' => 10,
        'is_enabled' => true,
    ]);
    ProviderIdentity::query()->create([
        'user_id' => $this->user->id,
        'provider_key' => 'wannads',
        'external_user_id' => 'uwannads1',
    ]);

    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'shared-tx-1']))
        ->assertOk();
    $this->getJson(TheoremReachFixtures::rewardPath(['transaction_id' => 'shared-tx-1']))
        ->assertOk();

    expect(AdActivity::query()->where('provider_transaction_id', 'shared-tx-1')->count())->toBe(2)
        ->and(PointLedgerEntry::query()->count())->toBe(2);
});
