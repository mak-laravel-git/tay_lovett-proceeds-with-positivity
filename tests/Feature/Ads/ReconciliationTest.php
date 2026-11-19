<?php

use App\Enums\ActivityStatus;
use App\Enums\ReconciliationStatus;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdReconciliation;
use App\Models\AdReconciliationLine;
use App\Models\AdSetting;
use App\Models\PointBalance;
use App\Models\PointLedgerEntry;
use App\Services\Ads\ReconciliationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

const RECON_DAY = '2026-11-10';
const RECON_TZ = 'America/New_York';
const RECON_URL = 'api.tapresearch.com/supply_api/suppliers/daily_stats*';

beforeEach(function () {
    Http::preventStrayRequests();
    Carbon::setTestNow(CarbonImmutable::parse('2026-11-12 12:00:00', RECON_TZ));

    AdSetting::current();
    $this->provider = AdProvider::factory()->withReporting()->create();
});

/** A locally recorded, verified postback confirmed on the reconciled day. */
function recordedPostback(string $user, int $micros, string $at = '2026-11-10 12:00:00'): AdActivity
{
    return AdActivity::factory()->approved($micros, 25)->create([
        'provider_user_id' => $user,
        // Stored as UTC, exactly as the app writes it; the settlement timezone is only used to pick the day.
        'provider_confirmed_at' => CarbonImmutable::parse($at, RECON_TZ)->utc(),
    ]);
}

/**
 * @param  list<array{user: string, revenue: string, country?: string}>  $rows
 */
function providerReports(array $rows, string $date = RECON_DAY): void
{
    Http::fake([RECON_URL => Http::response(array_map(fn (array $row): array => [
        'date' => $date,
        'app_name' => 'Our App',
        'country_abbr' => $row['country'] ?? 'US',
        'country_name' => 'United States',
        'revenue' => $row['revenue'],
        'user_identifier' => $row['user'],
        'platform' => 'web',
        'impressions' => 1,
    ], $rows))]);
}

function reconcile(string $date = RECON_DAY): AdReconciliation
{
    return app(ReconciliationService::class)->reconcile(test()->provider, $date);
}

test('provider-reported revenue matching the local postbacks is recorded as matched', function () {
    recordedPostback('uone', 1_320_000);
    providerReports([['user' => 'uone', 'revenue' => '1.32']]);

    $record = reconcile();

    expect($record->status)->toBe(ReconciliationStatus::Matched)
        ->and($record->provider_revenue_micros)->toBe(1_320_000)
        ->and($record->local_revenue_micros)->toBe(1_320_000)
        ->and($record->difference_micros)->toBe(0)
        ->and($record->local_transaction_count)->toBe(1)
        ->and($record->report_date)->toBe(RECON_DAY)
        ->and($record->timezone)->toBe(RECON_TZ)
        ->and($record->country_breakdown)->toBe(['US' => 1_320_000])
        ->and($record->lines)->toHaveCount(1)
        ->and($record->lines->first()->flag)->toBe(AdReconciliationLine::FLAG_MATCHED);
});

test('the provider total is compared with the sum of local postback revenue', function () {
    recordedPostback('uone', 1_000_000);
    recordedPostback('utwo', 2_500_000);
    providerReports([['user' => 'uone', 'revenue' => '1.00'], ['user' => 'utwo', 'revenue' => '2.50']]);

    $record = reconcile();

    expect($record->local_revenue_micros)->toBe(3_500_000)
        ->and($record->provider_revenue_micros)->toBe(3_500_000)
        ->and($record->status)->toBe(ReconciliationStatus::Matched);
});

test('a revenue difference is flagged as a discrepancy with the amount and the users involved', function () {
    recordedPostback('uone', 1_000_000);
    providerReports([
        ['user' => 'uone', 'revenue' => '1.00'],
        ['user' => 'umissing', 'revenue' => '0.80'],
    ]);

    $record = reconcile();

    expect($record->status)->toBe(ReconciliationStatus::Discrepancy)
        ->and($record->difference_micros)->toBe(800_000)
        ->and($record->flag_counts)->toBe([
            AdReconciliationLine::FLAG_MATCHED => 1,
            AdReconciliationLine::FLAG_MISSING_LOCAL => 1,
        ]);

    $line = $record->lines()->where('external_user_id', 'umissing')->sole();
    expect($line->flag)->toBe(AdReconciliationLine::FLAG_MISSING_LOCAL)
        ->and($line->provider_revenue_micros)->toBe(800_000)
        ->and($line->local_revenue_micros)->toBe(0);
});

test('revenue we recorded but the provider did not report is flagged for that user', function () {
    recordedPostback('uone', 1_000_000);
    recordedPostback('ughost', 600_000);
    providerReports([['user' => 'uone', 'revenue' => '1.00']]);

    $record = reconcile();

    expect($record->status)->toBe(ReconciliationStatus::Discrepancy)
        ->and($record->difference_micros)->toBe(-600_000)
        ->and($record->lines()->where('external_user_id', 'ughost')->value('flag'))->toBe(AdReconciliationLine::FLAG_MISSING_PROVIDER);
});

test('a difference inside the rounding tolerance still matches', function () {
    // The reporting API rounds to cents, so one cent either way is not a real difference.
    recordedPostback('uone', 1_324_000);
    providerReports([['user' => 'uone', 'revenue' => '1.33']]);

    expect(reconcile()->status)->toBe(ReconciliationStatus::Matched);
});

test('a reconciliation discrepancy never changes an individual transaction or any reward', function () {
    $activity = recordedPostback('uone', 1_000_000);
    PointLedgerEntry::query()->create(['user_id' => $activity->user_id, 'activity_id' => $activity->id, 'entry_type' => 'reward', 'points' => 25, 'idempotency_key' => 'reward:tapresearch:x', 'description' => 'Survey completed']);
    PointBalance::query()->create(['user_id' => $activity->user_id, 'balance' => 25, 'lifetime_earned' => 25]);

    // The provider reports far less than we recorded.
    providerReports([['user' => 'uone', 'revenue' => '0.10']]);

    $before = AdActivity::query()->get()->toArray();
    $record = reconcile();

    expect($record->status)->toBe(ReconciliationStatus::Discrepancy)
        ->and(AdActivity::query()->get()->toArray())->toBe($before)
        ->and($activity->fresh()->status)->toBe(ActivityStatus::Approved)
        ->and($activity->fresh()->awarded_points)->toBe(25)
        ->and(PointLedgerEntry::query()->count())->toBe(1)
        ->and(PointBalance::query()->where('user_id', $activity->user_id)->value('balance'))->toBe(25);
});

test('a day with no provider rows yet is pending while delayed reporting may still arrive', function () {
    recordedPostback('uone', 1_000_000, '2026-11-11 12:00:00');
    providerReports([], '2026-11-11');

    $record = reconcile('2026-11-11');

    expect($record->status)->toBe(ReconciliationStatus::Pending)
        ->and($record->provider_revenue_micros)->toBeNull()
        ->and($record->difference_micros)->toBeNull()
        ->and($record->local_revenue_micros)->toBe(1_000_000);
});

test('a day that is still in progress is always pending', function () {
    recordedPostback('uone', 1_000_000, '2026-11-12 08:00:00');
    providerReports([['user' => 'uone', 'revenue' => '1.00']], '2026-11-12');

    expect(reconcile('2026-11-12')->status)->toBe(ReconciliationStatus::Pending);
});

test('a day the provider never reported becomes a discrepancy once the recheck window has passed', function () {
    recordedPostback('uone', 1_000_000, '2026-11-01 12:00:00');
    providerReports([], '2026-11-01');

    expect(reconcile('2026-11-01')->status)->toBe(ReconciliationStatus::Discrepancy);
});

test('a day with nothing on either side matches', function () {
    providerReports([]);

    expect(reconcile()->status)->toBe(ReconciliationStatus::Matched);
});

test('reporting without an access token is unavailable and makes no request', function () {
    Http::fake();
    $this->provider->update(['settings' => ['api_token' => 't', 'api_secret' => 's']]);

    $record = reconcile();

    expect($record->status)->toBe(ReconciliationStatus::Unavailable)
        ->and($record->last_error)->toBe('reporting_token_missing')
        ->and($record->provider_revenue_micros)->toBeNull();

    Http::assertNothingSent();
});

test('access denied by the reporting API is unavailable, not matched', function () {
    Http::fake([RECON_URL => Http::response(['error' => 'forbidden'], 403)]);

    expect(reconcile()->status)->toBe(ReconciliationStatus::Unavailable);
});

test('a reporting outage is a failure and a later failed re-run never downgrades a good result', function () {
    recordedPostback('uone', 1_000_000);
    Http::fake([RECON_URL => Http::sequence()
        ->push([['date' => RECON_DAY, 'country_abbr' => 'US', 'revenue' => '1.00', 'user_identifier' => 'uone']])
        ->push([], 500)]);
    expect(reconcile()->status)->toBe(ReconciliationStatus::Matched);

    $again = reconcile();

    expect($again->status)->toBe(ReconciliationStatus::Matched)
        ->and($again->provider_revenue_micros)->toBe(1_000_000)
        ->and($again->last_error)->toBe('provider_error');

    expect(AdReconciliation::query()->count())->toBe(1);
});

test('a first-ever failed reconciliation is stored so the dashboard can show why', function () {
    Http::fake([RECON_URL => Http::response([], 500)]);

    $record = reconcile();

    expect($record->status)->toBe(ReconciliationStatus::Failed)
        ->and($record->last_error)->toBe('provider_error');
});

test('running the same day again updates one record instead of creating duplicates', function () {
    recordedPostback('uone', 1_000_000);
    providerReports([['user' => 'uone', 'revenue' => '1.00']]);

    reconcile();
    reconcile();
    $third = reconcile();

    expect(AdReconciliation::query()->count())->toBe(1)
        ->and(AdReconciliationLine::query()->count())->toBe(1)
        ->and($third->rechecked_count)->toBe(2);
});

test('a delayed postback is folded in when the day is re-checked', function () {
    providerReports([['user' => 'ulate', 'revenue' => '0.50']]);

    $first = reconcile();
    expect($first->status)->toBe(ReconciliationStatus::Discrepancy);

    // The postback finally arrives (it is received on the 11th, so counts on that day, not the 10th).
    recordedPostback('ulate', 500_000, '2026-11-10 20:00:00');

    expect(reconcile()->status)->toBe(ReconciliationStatus::Matched);
});

test('local revenue is counted on the day the postback was received in the settlement timezone', function () {
    // 23:30 on the 10th in New York is already the 11th in UTC; it must still count on the 10th.
    recordedPostback('uone', 1_000_000, '2026-11-10 23:30:00');
    recordedPostback('utwo', 700_000, '2026-11-11 00:30:00');
    providerReports([['user' => 'uone', 'revenue' => '1.00']]);

    $record = reconcile();

    expect($record->local_revenue_micros)->toBe(1_000_000)
        ->and($record->status)->toBe(ReconciliationStatus::Matched);
});

test('callbacks that failed verification never count as locally recorded revenue', function () {
    recordedPostback('uone', 1_000_000);
    AdActivity::factory()->create([
        'provider_user_id' => 'uforged',
        'provider_confirmed_at' => CarbonImmutable::parse('2026-11-10 12:00:00', RECON_TZ)->utc(),
        'publisher_revenue_micros' => 9_000_000,
        'callback_verification_status' => 'invalid_signature',
    ]);
    providerReports([['user' => 'uone', 'revenue' => '1.00']]);

    expect(reconcile()->local_revenue_micros)->toBe(1_000_000);
});

test('the reconcile command reconciles the requested day and re-checks earlier ones', function () {
    recordedPostback('uone', 1_000_000);
    providerReports([['user' => 'uone', 'revenue' => '1.00']]);

    $this->artisan('ads:reconcile-tapresearch', ['--date' => RECON_DAY, '--days' => 2])->assertSuccessful();

    expect(AdReconciliation::query()->count())->toBe(3)
        ->and(AdReconciliation::query()->whereDate('report_date', RECON_DAY)->value('status'))->toBe(ReconciliationStatus::Matched);

    // Safe to run again: still three records.
    $this->artisan('ads:reconcile-tapresearch', ['--date' => RECON_DAY, '--days' => 2])->assertSuccessful();
    expect(AdReconciliation::query()->count())->toBe(3);
});

test('the command defaults to yesterday in the settlement timezone', function () {
    Http::fake([RECON_URL => Http::response([])]);

    $this->artisan('ads:reconcile-tapresearch')->assertSuccessful();

    expect(AdReconciliation::query()->sole()->report_date)->toBe('2026-11-11');
});

test('the command is scheduled shortly after midnight in the settlement timezone', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('ads:reconcile-tapresearch')
        ->assertSuccessful();
});

test('the command fails loudly when reporting breaks', function () {
    Http::fake([RECON_URL => Http::response([], 500)]);

    $this->artisan('ads:reconcile-tapresearch', ['--date' => RECON_DAY])->assertFailed();
});
