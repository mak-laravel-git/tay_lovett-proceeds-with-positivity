<?php

use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdRewardRule;
use App\Models\AdSetting;
use App\Models\LedgerEntry;
use App\Models\ProviderIdentity;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Ads\UserRevenueShare;
use Tests\Support\TapResearchFixtures;

beforeEach(function () {
    AdProvider::factory()->create();
    AdRewardRule::query()->create([
        'provider_key' => null,
        'activity_type' => 'survey',
        'points' => 25,
        'is_enabled' => true,
    ]);

    $this->user = User::factory()->create();
    ProviderIdentity::query()->create([
        'user_id' => $this->user->id,
        'provider_key' => 'tapresearch',
        'external_user_id' => 'uexample',
    ]);
    $this->activity = AdActivity::factory()->for($this->user)->create([
        'provider_user_id' => 'uexample',
        'offer_identifier' => '555001',
    ]);
});

function sharePostback(AdActivity $activity, array $overrides = []): string
{
    return TapResearchFixtures::postbackUrl(array_merge([
        'activity_id' => $activity->id,
        'offer_identifier' => $activity->offer_identifier,
        'uid' => $activity->provider_user_id,
    ], $overrides));
}

test('sandbox revenue share stays off by default', function () {
    $this->getJson(sharePostback($this->activity, [
        'cpid' => 'cpid-share-off',
        'revenue' => '30.00',
    ]))->assertOk();

    expect(LedgerEntry::query()->where('entry_type', UserRevenueShare::ENTRY_TYPE)->count())->toBe(0)
        ->and(Wallet::query()->where('user_id', $this->user->id)->value('pending_cents'))->toBeNull();
});

test('enabled sandbox share credits floor one-third to pending only once', function () {
    AdSetting::current()->update(['user_revenue_share_enabled' => true]);

    $url = sharePostback($this->activity, [
        'cpid' => 'cpid-share-30',
        'revenue' => '30.00',
    ]);

    $this->getJson($url)->assertOk()->assertJson(['status' => 'credited']);
    $this->getJson($url)->assertOk()->assertJson(['status' => 'duplicate']);

    $wallet = Wallet::query()->where('user_id', $this->user->id)->sole();
    $entry = LedgerEntry::query()->where('entry_type', UserRevenueShare::ENTRY_TYPE)->sole();

    expect($wallet->pending_cents)->toBe(1000)
        ->and($wallet->available_cents)->toBe(0)
        ->and($wallet->reserved_cents)->toBe(0)
        ->and($entry->amount_cents)->toBe(1000)
        ->and($entry->idempotency_key)->toBe('revenue-share:tapresearch:cpid-share-30')
        ->and($entry->description)->toContain('SANDBOX')
        ->and(LedgerEntry::query()->where('entry_type', UserRevenueShare::ENTRY_TYPE)->count())->toBe(1);
});

test('one dollar eligible revenue yields thirty-three pending cents', function () {
    AdSetting::current()->update(['user_revenue_share_enabled' => true]);

    $this->getJson(sharePostback($this->activity, [
        'cpid' => 'cpid-share-1',
        'revenue' => '1.00',
    ]))->assertOk();

    expect(Wallet::query()->where('user_id', $this->user->id)->value('pending_cents'))->toBe(33);
});
