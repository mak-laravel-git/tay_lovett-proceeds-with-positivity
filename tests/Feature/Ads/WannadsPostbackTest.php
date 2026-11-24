<?php

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
use Tests\Support\WannadsFixtures;

beforeEach(function () {
    AdProvider::factory()->create([
        'key' => 'wannads',
        'label' => 'Wannads (Offerwall)',
        'is_enabled' => true,
        'settings' => [
            'api_token' => WannadsFixtures::API_KEY,
            'api_secret' => WannadsFixtures::SECRET,
        ],
        'config' => [
            'sandbox_mode' => true,
            'production_enabled' => false,
            'allowed_callback_ips' => [],
        ],
    ]);

    AdRewardRule::query()->create([
        'provider_key' => 'wannads',
        'activity_type' => ActivityType::Offerwall->value,
        'points' => 15,
        'is_enabled' => true,
    ]);

    $this->user = User::factory()->create();
    ProviderIdentity::query()->create([
        'user_id' => $this->user->id,
        'provider_key' => 'wannads',
        'external_user_id' => 'uwannads1',
    ]);
});

test('W-003 valid wannads credited postback creates one ledger credit and returns OK', function () {
    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'w-qa-100', 'reward' => '100', 'payout' => '0.75']))
        ->assertOk()
        ->assertSee('OK');

    expect(PointLedgerEntry::query()->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $this->user->id)->value('balance'))->toBe(15)
        ->and(AdActivity::query()->where('provider_transaction_id', 'w-qa-100')->sole()->status)->toBe(ActivityStatus::Approved);
});

test('W-004 wrong signature rejects with ERROR and zero credit', function () {
    $url = WannadsFixtures::postbackUrl(['transaction_id' => 'w-bad-sig', 'reward' => '100']);
    $tampered = preg_replace('/reward=100/', 'reward=1000', $url, 1);

    $this->get($tampered)->assertUnauthorized()->assertSee('ERROR');

    expect(PointLedgerEntry::query()->count())->toBe(0)
        ->and(ProviderCallback::query()->sole()->result->value)->toBe('invalid_signature');
});

test('W-006 duplicate wannads callbacks credit once and keep returning OK', function () {
    $url = WannadsFixtures::postbackUrl(['transaction_id' => 'w-dup-1']);

    $this->get($url)->assertOk()->assertSee('OK');
    $this->get($url)->assertOk()->assertSee('OK');
    $this->get($url)->assertOk()->assertSee('OK');

    expect(PointLedgerEntry::query()->count())->toBe(1)
        ->and(ProviderCallback::query()->where('result', 'duplicate')->count())->toBe(2);
});

test('W-007 rejected status reverses a prior credit once', function () {
    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'w-rev-1', 'status' => 'credited']))
        ->assertOk();

    expect(PointBalance::query()->where('user_id', $this->user->id)->value('balance'))->toBe(15);

    $this->get(WannadsFixtures::postbackUrl([
        'transaction_id' => 'w-rev-1',
        'status' => 'rejected',
        'reward' => '-100',
    ]))->assertOk()->assertSee('OK');

    $this->get(WannadsFixtures::postbackUrl([
        'transaction_id' => 'w-rev-1',
        'status' => 'rejected',
        'reward' => '-100',
    ]))->assertOk()->assertSee('OK');

    expect(PointLedgerEntry::query()->where('entry_type', 'reversal')->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $this->user->id)->value('balance'))->toBe(0)
        ->and(AdActivity::query()->where('provider_transaction_id', 'w-rev-1')->sole()->reward_status)->toBe(RewardStatus::Reversed);
});

test('W-008 rejection before credit blocks a later credited callback', function () {
    $this->get(WannadsFixtures::postbackUrl([
        'transaction_id' => 'w-order-1',
        'status' => 'rejected',
        'reward' => '-100',
    ]))->assertOk();

    $this->get(WannadsFixtures::postbackUrl([
        'transaction_id' => 'w-order-1',
        'status' => 'credited',
        'reward' => '100',
    ]))->assertOk()->assertSee('OK');

    expect(PointLedgerEntry::query()->count())->toBe(0)
        ->and(AdActivity::query()->where('provider_transaction_id', 'w-order-1')->sole()->status)->toBe(ActivityStatus::Rejected);
});

test('W-005 unknown user is stored without credit', function () {
    $this->get(WannadsFixtures::postbackUrl([
        'user_id' => 'nobody',
        'transaction_id' => 'w-ghost-1',
    ]))->assertOk()->assertSee('OK');

    expect(PointLedgerEntry::query()->count())->toBe(0)
        ->and(AdActivity::query()->where('provider_transaction_id', 'w-ghost-1')->sole()->user_id)->toBeNull();
});

test('W-001 launch URL uses official wall host and opaque user id without secret', function () {
    $this->actingAs($this->user)
        ->postJson('/surveys/offers', ['provider' => 'wannads'])
        ->assertOk()
        ->assertJsonPath('provider', 'wannads');

    $requestId = (int) $this->actingAs($this->user)
        ->postJson('/surveys/offers', ['provider' => 'wannads'])
        ->json('request_id');

    $start = $this->actingAs($this->user)
        ->postJson('/surveys/start', [
            'request_id' => $requestId,
            'offer_identifier' => 'wannads-wall',
        ])
        ->assertOk()
        ->json();

    expect($start['url'])
        ->toStartWith('https://earn.wannads.com/wall?')
        ->and($start['url'])->toContain('apiKey='.WannadsFixtures::API_KEY)
        ->and($start['url'])->toContain('userId=uwannads1')
        ->and($start['url'])->not->toContain(WannadsFixtures::SECRET);
});

test('W-002 disabled wannads cannot win a launch request', function () {
    AdProvider::query()->where('key', 'wannads')->update(['is_enabled' => false]);

    $this->actingAs($this->user)
        ->postJson('/surveys/offers', ['provider' => 'wannads'])
        ->assertOk()
        ->assertJsonPath('provider', null)
        ->assertJsonPath('offers', []);
});

test('W-010 callback from non-allowlisted IP is rejected when enforced', function () {
    AdProvider::query()->where('key', 'wannads')->update([
        'config' => [
            'sandbox_mode' => true,
            'allowed_callback_ips' => ['3.22.177.178'],
            'enforce_callback_ips_in_local' => true,
        ],
    ]);

    $this->get(WannadsFixtures::postbackUrl(['transaction_id' => 'w-ip-1']))
        ->assertForbidden()
        ->assertSee('ERROR');

    expect(PointLedgerEntry::query()->count())->toBe(0);
});
