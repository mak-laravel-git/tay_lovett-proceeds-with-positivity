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
use App\Services\Ads\Providers\TapResearch\TapResearchSignature;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Support\TapResearchFixtures;

beforeEach(function () {
    AdProvider::factory()->create();
    AdRewardRule::query()->create(['provider_key' => null, 'activity_type' => 'survey', 'points' => 25, 'is_enabled' => true]);

    $this->user = User::factory()->create();
    ProviderIdentity::query()->create(['user_id' => $this->user->id, 'provider_key' => 'tapresearch', 'external_user_id' => 'uexample']);
    $this->activity = AdActivity::factory()->for($this->user)->create([
        'provider_user_id' => 'uexample',
        'offer_identifier' => '555001',
    ]);
});

/**
 * A signed postback for the user's started survey.
 *
 * @param  array<string, string|int|null>  $overrides
 */
function postbackFor(AdActivity $activity, array $overrides = []): string
{
    return TapResearchFixtures::postbackUrl(array_merge([
        'activity_id' => $activity->id,
        'offer_identifier' => $activity->offer_identifier,
        'uid' => $activity->provider_user_id,
    ], $overrides));
}

test('a verified postback approves the exact survey and records the provider confirmation', function () {
    $this->freezeSecond();

    $this->getJson(postbackFor($this->activity, ['cpid' => 'cpid-approve-1']))
        ->assertOk()
        ->assertJson(['status' => 'credited']);

    $activity = $this->activity->fresh();

    expect($activity->status)->toBe(ActivityStatus::Approved)
        ->and($activity->provider_transaction_id)->toBe('cpid-approve-1')
        ->and($activity->survey_outcome)->toBe(1)
        ->and($activity->payout_type)->toBe(3)
        ->and($activity->provider_confirmed_at->equalTo(now()))->toBeTrue()
        ->and($activity->callback_verification_status)->toBe('verified')
        ->and($activity->callback_payload)->toHaveKey('cpid', 'cpid-approve-1')
        // Secrets never end up in the stored payload.
        ->and($activity->callback_payload)->not->toHaveKeys(['sig', 'api_token']);
});

test('publisher revenue and the provider payout are stored separately from the user reward', function () {
    $this->getJson(postbackFor($this->activity, ['revenue' => '1.32', 'payout_amount' => 50]))->assertOk();

    $activity = $this->activity->fresh();

    expect($activity->publisher_revenue_micros)->toBe(1_320_000)
        ->and($activity->provider_payout_amount)->toBe(50)
        ->and($activity->payout_currency)->toBe('points')
        ->and($activity->currency)->toBe('USD')
        // The configured rule (25), not the provider payout (50) and not the advertised reward, decides points.
        ->and($activity->awarded_points)->toBe(25);
});

test('the configured points are credited once and recorded in the point ledger', function () {
    $this->freezeSecond();

    $this->getJson(postbackFor($this->activity, ['cpid' => 'cpid-points-1']))->assertOk();

    $activity = $this->activity->fresh();
    $entry = PointLedgerEntry::query()->sole();

    expect($activity->awarded_points)->toBe(25)
        ->and($activity->reward_status)->toBe(RewardStatus::Credited)
        ->and($activity->reward_credited_at->equalTo(now()))->toBeTrue()
        ->and($entry->user_id)->toBe($this->user->id)
        ->and($entry->activity_id)->toBe($activity->id)
        ->and($entry->points)->toBe(25)
        ->and($entry->idempotency_key)->toBe('reward:tapresearch:cpid-points-1');

    $balance = PointBalance::query()->where('user_id', $this->user->id)->sole();
    expect($balance->balance)->toBe(25)->and($balance->lifetime_earned)->toBe(25);
});

test('a duplicate postback is acknowledged but never credits the user twice', function () {
    $url = postbackFor($this->activity, ['cpid' => 'cpid-dup-1']);

    $this->getJson($url)->assertOk()->assertJson(['status' => 'credited']);
    $this->getJson($url)->assertOk()->assertJson(['status' => 'duplicate']);
    $this->getJson($url)->assertOk()->assertJson(['status' => 'duplicate']);

    expect(PointLedgerEntry::query()->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $this->user->id)->value('balance'))->toBe(25)
        ->and(AdActivity::query()->where('provider_transaction_id', 'cpid-dup-1')->count())->toBe(1)
        // Every delivery, including the duplicates, is logged.
        ->and(ProviderCallback::query()->count())->toBe(3)
        ->and(ProviderCallback::query()->where('result', 'duplicate')->count())->toBe(2);
});

test('a duplicate that loses the unique-index race is still acknowledged without crediting', function () {
    $armed = true;

    AdActivity::saving(function () use (&$armed): void {
        if ($armed) {
            $armed = false;

            throw new UniqueConstraintViolationException('sqlite', 'update ad_activities', [], new Exception('UNIQUE constraint failed'));
        }
    });

    $this->getJson(postbackFor($this->activity, ['cpid' => 'cpid-race-1']))
        ->assertOk()
        ->assertJson(['status' => 'duplicate']);

    expect(PointLedgerEntry::query()->count())->toBe(0)
        ->and(ProviderCallback::query()->where('result', 'duplicate')->count())->toBe(1);
});

test('an invalid signature is logged and never approves or rewards anything', function () {
    $url = postbackFor($this->activity, ['revenue' => '1.32']);
    $tampered = str_replace('revenue=1.32', 'revenue=999.00', $url);

    $this->getJson($tampered)->assertUnauthorized()->assertJson(['status' => 'invalid_signature']);

    expect(PointLedgerEntry::query()->count())->toBe(0)
        ->and($this->activity->fresh()->status)->toBe(ActivityStatus::Started)
        ->and($this->activity->fresh()->publisher_revenue_micros)->toBe(0)
        ->and($this->activity->fresh()->provider_transaction_id)->toBeNull();

    $log = ProviderCallback::query()->sole();
    expect($log->result->value)->toBe('invalid_signature')
        ->and($log->signature_valid)->toBeFalse()
        // Revenue from an unauthenticated request is never counted.
        ->and($log->revenue_micros)->toBe(0);
});

test('a postback signed with the wrong secret is rejected', function () {
    $this->getJson(TapResearchFixtures::postbackUrl(['activity_id' => $this->activity->id], 'not-the-secret'))
        ->assertUnauthorized();

    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('a missing signature is rejected', function () {
    $this->getJson('/api/webhooks/tapresearch/reward?cpid=abc&payout_type=3&survey_outcome=1&revenue=1.00&api_token='.TapResearchFixtures::TOKEN)
        ->assertUnauthorized();

    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('a correctly signed postback with the wrong api token is rejected', function () {
    $this->getJson(postbackFor($this->activity, ['api_token' => 'someone-elses-token']))
        ->assertUnauthorized()
        ->assertJson(['status' => 'invalid_token']);

    expect(PointLedgerEntry::query()->count())->toBe(0)
        ->and(ProviderCallback::query()->sole()->result->value)->toBe('invalid_token');
});

test('a disqualified or over-quota outcome is recorded as rejected and earns no points', function (int $outcome) {
    $this->getJson(postbackFor($this->activity, ['survey_outcome' => $outcome, 'revenue' => '0.20', 'cpid' => 'cpid-dq-'.$outcome]))
        ->assertOk()
        ->assertJson(['status' => 'unsuccessful_outcome']);

    $activity = $this->activity->fresh();

    expect($activity->status)->toBe(ActivityStatus::Rejected)
        ->and($activity->survey_outcome)->toBe($outcome)
        ->and($activity->reward_status)->toBe(RewardStatus::NotEligible)
        ->and($activity->awarded_points)->toBe(0)
        // The provider still paid us for the time spent, so the revenue is recorded.
        ->and($activity->publisher_revenue_micros)->toBe(200_000)
        ->and(PointLedgerEntry::query()->count())->toBe(0);
})->with([2, 3]);

test('only a survey payout can be a completion: other payout types earn nothing', function () {
    $this->getJson(postbackFor($this->activity, ['payout_type' => 10, 'cpid' => 'cpid-bonus-1', 'revenue' => '0.50']))
        ->assertOk()
        ->assertJson(['status' => 'recorded']);

    // The started survey is untouched; the bonus is its own transaction.
    expect($this->activity->fresh()->status)->toBe(ActivityStatus::Started)
        ->and(PointLedgerEntry::query()->count())->toBe(0);

    $bonus = AdActivity::query()->where('provider_transaction_id', 'cpid-bonus-1')->sole();
    expect($bonus->source)->toBe(AdActivity::SOURCE_POSTBACK_ONLY)
        ->and($bonus->publisher_revenue_micros)->toBe(500_000);
});

test('a postback for an unknown activity is stored as its own transaction and awards nothing', function () {
    $unknown = (string) Str::ulid();

    $this->getJson(TapResearchFixtures::postbackUrl(['activity_id' => $unknown, 'uid' => 'uexample', 'cpid' => 'cpid-unmatched-1']))
        ->assertOk()
        ->assertJson(['status' => 'unmatched']);

    $orphan = AdActivity::query()->where('provider_transaction_id', 'cpid-unmatched-1')->sole();

    expect($orphan->source)->toBe(AdActivity::SOURCE_POSTBACK_ONLY)
        ->and($orphan->user_id)->toBe($this->user->id)
        ->and($orphan->reward_status)->toBe(RewardStatus::NotEligible)
        ->and($orphan->reward_note)->toBe('unmatched_activity')
        ->and($orphan->publisher_revenue_micros)->toBe(1_320_000)
        ->and($this->activity->fresh()->status)->toBe(ActivityStatus::Started)
        ->and(PointLedgerEntry::query()->count())->toBe(0);
});

test('a postback for an unknown user is kept without a user and awards nothing', function () {
    $this->getJson(TapResearchFixtures::postbackUrl(['uid' => 'nobody-we-know', 'cpid' => 'cpid-ghost-1']))
        ->assertOk()
        ->assertJson(['status' => 'unmatched']);

    $orphan = AdActivity::query()->where('provider_transaction_id', 'cpid-ghost-1')->sole();

    expect($orphan->user_id)->toBeNull()
        ->and($orphan->provider_user_id)->toBe('nobody-we-know')
        ->and(PointLedgerEntry::query()->count())->toBe(0);
});

test('an activity is matched by user and survey when the pass-through value is missing', function () {
    $this->getJson(TapResearchFixtures::postbackUrl(['uid' => 'uexample', 'offer_identifier' => '555001', 'cpid' => 'cpid-fallback-1']))
        ->assertOk()
        ->assertJson(['status' => 'credited']);

    expect($this->activity->fresh()->status)->toBe(ActivityStatus::Approved)
        ->and($this->activity->fresh()->provider_transaction_id)->toBe('cpid-fallback-1');
});

test('a postback naming a different user than the activity belongs to is not rewarded', function () {
    $this->getJson(postbackFor($this->activity, ['uid' => 'someone-else', 'cpid' => 'cpid-mismatch-1']))
        ->assertOk()
        ->assertJson(['status' => 'unmatched']);

    expect($this->activity->fresh()->status)->toBe(ActivityStatus::Started)
        ->and(PointLedgerEntry::query()->count())->toBe(0);
});

test('a second payout for an already confirmed survey does not reward it again', function () {
    $this->getJson(postbackFor($this->activity, ['cpid' => 'cpid-first']))->assertOk()->assertJson(['status' => 'credited']);
    $this->getJson(postbackFor($this->activity, ['cpid' => 'cpid-second']))->assertOk()->assertJson(['status' => 'unmatched']);

    expect(PointLedgerEntry::query()->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $this->user->id)->value('balance'))->toBe(25);
});

test('an approved survey without any enabled reward rule is approved but earns no points', function () {
    AdRewardRule::query()->delete();

    $this->getJson(postbackFor($this->activity))->assertOk()->assertJson(['status' => 'not_rewarded']);

    $activity = $this->activity->fresh();
    expect($activity->status)->toBe(ActivityStatus::Approved)
        ->and($activity->reward_status)->toBe(RewardStatus::NotEligible)
        ->and($activity->reward_note)->toBe('no_reward_rule')
        ->and(PointLedgerEntry::query()->count())->toBe(0);
});

test('a provider-specific reward rule overrides the global one', function () {
    AdRewardRule::query()->create(['provider_key' => 'tapresearch', 'activity_type' => ActivityType::Survey->value, 'points' => 40, 'is_enabled' => true]);

    $this->getJson(postbackFor($this->activity))->assertOk();

    expect($this->activity->fresh()->awarded_points)->toBe(40);
});

test('a disabled provider rule falls back to the enabled global rule', function () {
    AdRewardRule::query()->create(['provider_key' => 'tapresearch', 'activity_type' => ActivityType::Survey->value, 'points' => 40, 'is_enabled' => false]);

    $this->getJson(postbackFor($this->activity))->assertOk();

    expect($this->activity->fresh()->awarded_points)->toBe(25);
});

test('a disabled global rule means no points', function () {
    AdRewardRule::query()->update(['is_enabled' => false]);

    $this->getJson(postbackFor($this->activity))->assertOk();

    expect($this->activity->fresh()->reward_status)->toBe(RewardStatus::NotEligible)
        ->and(PointLedgerEntry::query()->count())->toBe(0);
});

test('a postback that arrives after midnight updates the original survey and counts on the day it was received', function () {
    $timezone = 'America/New_York';
    $startedAt = CarbonImmutable::parse('2026-11-10 23:50:00', $timezone);

    Carbon::setTestNow($startedAt);
    $activity = AdActivity::factory()->for($this->user)->create([
        'provider_user_id' => 'uexample',
        'survey_started_at' => $startedAt,
    ]);

    Carbon::setTestNow($startedAt->addMinutes(30)); // 00:20 on the 11th

    $this->getJson(postbackFor($activity, ['cpid' => 'cpid-midnight-1', 'revenue' => '2.00']))->assertOk();

    $fresh = $activity->fresh();
    expect(AdActivity::query()->whereKey($activity->id)->count())->toBe(1)
        ->and($fresh->status)->toBe(ActivityStatus::Approved)
        ->and($fresh->survey_started_at->setTimezone($timezone)->toDateString())->toBe('2026-11-10')
        ->and($fresh->provider_confirmed_at->setTimezone($timezone)->toDateString())->toBe('2026-11-11')
        ->and($fresh->publisher_revenue_micros)->toBe(2_000_000);
});

test('a delayed postback still approves a survey that had already expired', function () {
    $this->activity->forceFill(['status' => ActivityStatus::Expired, 'survey_started_at' => now()->subDays(3)])->save();

    $this->getJson(postbackFor($this->activity, ['cpid' => 'cpid-late-1']))
        ->assertOk()
        ->assertJson(['status' => 'credited']);

    $fresh = $this->activity->fresh();
    expect($fresh->status)->toBe(ActivityStatus::Approved)
        ->and($fresh->awarded_points)->toBe(25)
        ->and($fresh->provider_confirmed_at->diffInDays($fresh->survey_started_at, true))->toBeGreaterThanOrEqual(2);
});

test('a postback with no transaction id is refused without crediting', function () {
    $this->getJson(postbackFor($this->activity, ['cpid' => null]))->assertStatus(400);

    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('a postback for an unknown or unconfigured provider is rejected', function () {
    $this->getJson('/api/webhooks/nope/reward?x=1&sig=abc')->assertNotFound();

    AdProvider::query()->where('key', 'tapresearch')->update(['settings' => null]);
    $this->getJson(postbackFor($this->activity))->assertNotFound();

    expect(PointLedgerEntry::query()->count())->toBe(0);
});

test('postbacks stay reachable while the site is in maintenance mode', function () {
    $this->artisan('down')->assertSuccessful();

    try {
        $this->getJson(postbackFor($this->activity))->assertOk();
    } finally {
        $this->artisan('up')->assertSuccessful();
    }
});

test('the signature check follows the documented procedure for the raw query string', function () {
    $secret = 'doc-secret';
    // `%2B`/`%20` decode before signing; a literal `+` is kept as a plus sign, never turned into a space.
    $query = 'cpid=1&note=a%2Bb%20c&plain=x+y&payout_type=3';
    $sig = hash_hmac('md5', 'cpid=1&note=a+b c&plain=x+y&payout_type=3', $secret);

    expect(TapResearchSignature::verifyPostback($query.'&sig='.$sig, $secret))->toBeTrue()
        ->and(TapResearchSignature::verifyPostback($query.'&sig='.strtoupper($sig), $secret))->toBeTrue()
        ->and(TapResearchSignature::verifyPostback($query.'&sig='.$sig, 'other'))->toBeFalse()
        ->and(TapResearchSignature::verifyPostback($query.'&sig=', $secret))->toBeFalse()
        ->and(TapResearchSignature::verifyPostback($query, $secret))->toBeFalse()
        ->and(TapResearchSignature::verifyPostback('cpid=2&note=a%2Bb%20c&plain=x+y&payout_type=3&sig='.$sig, $secret))->toBeFalse();
});
