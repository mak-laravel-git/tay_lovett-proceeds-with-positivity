<?php

namespace App\Services\Ads;

use App\Enums\ActivityStatus;
use App\Enums\ReconciliationStatus;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdProviderPayout;
use App\Models\AdReconciliation;
use App\Models\AdSetting;
use App\Support\Micros;
use Carbon\CarbonImmutable;

/**
 * Numbers for the admin ads overview. Three kinds of revenue are kept apart and never blended:
 *
 *   1. locally recorded postback revenue  - what verified callbacks told us (reported by the provider, not cash)
 *   2. provider-reported revenue          - the provider's own daily report, via reconciliation
 *   3. settled / paid revenue             - recorded by hand by an administrator; no API reports it
 *
 * All money is held as integer micros and only turned into a display string at the very end.
 */
class AdsReport
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $settings = AdSetting::current();
        $timezone = in_array($settings->settlement_timezone, timezone_identifiers_list(), true)
            ? $settings->settlement_timezone
            : AdSetting::defaults()['settlement_timezone'];

        $now = CarbonImmutable::now($timezone);
        $today = $now->startOfDay();
        $yesterday = $today->subDay();
        $monthStart = $now->startOfMonth();

        $localToday = $this->localRevenue($today, $today->addDay());
        $localYesterday = $this->localRevenue($yesterday, $today);
        $localMonth = $this->localRevenue($monthStart, $today->addDay());
        $localTotal = (int) AdActivity::query()->where('callback_verification_status', 'verified')->sum('publisher_revenue_micros');

        $pointsDistributed = (int) AdActivity::query()->sum('awarded_points');
        $pointValue = $settings->point_usd_value_micros;
        $estimatedRemaining = $pointValue === null ? null : $localTotal - $pointsDistributed * (int) $pointValue;

        $reconYesterday = AdReconciliation::query()->whereDate('report_date', $yesterday->toDateString())->get();
        $reconMonth = AdReconciliation::query()
            ->whereDate('report_date', '>=', $monthStart->toDateString())
            ->whereDate('report_date', '<=', $today->toDateString())
            ->get();

        $paidTotal = (int) AdProviderPayout::query()->sum('amount_micros');
        $paidMonth = (int) AdProviderPayout::query()->whereDate('received_at', '>=', $monthStart->toDateString())->sum('amount_micros');

        $lastGood = AdReconciliation::query()
            ->whereIn('status', [ReconciliationStatus::Matched->value, ReconciliationStatus::Discrepancy->value])
            ->max('fetched_at');

        return [
            'timezone' => $timezone,
            'counts' => [
                // "Completed" = the user finished the flow (came back) or the provider confirmed it.
                'completed' => AdActivity::query()->where('source', AdActivity::SOURCE_APP)
                    ->where(fn ($q) => $q->whereNotNull('survey_returned_at')->orWhereNotNull('provider_confirmed_at'))->count(),
                'approved' => AdActivity::query()->where('status', ActivityStatus::Approved->value)->count(),
                'rejected' => AdActivity::query()->where('status', ActivityStatus::Rejected->value)->count(),
                'pending' => AdActivity::query()->where('source', AdActivity::SOURCE_APP)->whereIn('status', ActivityStatus::pendingValues())->count(),
                'unmatched' => AdActivity::query()->where('source', AdActivity::SOURCE_POSTBACK_ONLY)->count(),
            ],
            'points_distributed' => $pointsDistributed,
            'point_value_set' => $pointValue !== null,
            'estimated_remaining_usd' => $estimatedRemaining === null ? null : Micros::toDollars($estimatedRemaining),
            // 1. Locally recorded postback revenue (provider-reported to us; not cash received).
            'local_revenue' => [
                'today_usd' => Micros::toDollars($localToday),
                'yesterday_usd' => Micros::toDollars($localYesterday),
                'month_usd' => Micros::toDollars($localMonth),
                'total_usd' => Micros::toDollars($localTotal),
            ],
            // 2. Provider-reported revenue (reconciliation). Null when the provider has not reported.
            'provider_revenue' => [
                'yesterday_usd' => $this->providerTotal($reconYesterday),
                'month_usd' => $this->providerTotal($reconMonth),
                'yesterday_status' => $reconYesterday->first()?->status->value,
            ],
            // 3. Settled / paid revenue (manual entries only).
            'settled_revenue' => [
                'recorded' => AdProviderPayout::query()->exists(),
                'month_usd' => Micros::toDollars($paidMonth),
                'total_usd' => Micros::toDollars($paidTotal),
            ],
            'last_reconciled_at' => is_string($lastGood) ? CarbonImmutable::parse($lastGood)->toIso8601String() : null,
            'daily_chart' => $this->dailyChart($today, 14),
            'countries' => $this->countryBreakdown($today->subDays(30)),
            'providers' => AdProvider::query()->orderBy('priority')->orderBy('id')->get()->map(fn (AdProvider $provider): array => [
                'key' => $provider->key,
                'label' => $provider->label,
                'is_enabled' => $provider->is_enabled,
                'health' => $provider->health,
                'last_success_at' => $provider->last_success_at?->toIso8601String(),
                'last_error_category' => $provider->last_error_category,
            ])->all(),
        ];
    }

    /**
     * Sum of verified callback revenue received in [from, to) (instants in the settlement timezone).
     */
    public function localRevenue(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $appTimezone = (string) config('app.timezone');

        return (int) AdActivity::query()
            ->where('callback_verification_status', 'verified')
            ->where('provider_confirmed_at', '>=', $from->setTimezone($appTimezone))
            ->where('provider_confirmed_at', '<', $to->setTimezone($appTimezone))
            ->sum('publisher_revenue_micros');
    }

    /**
     * @param  iterable<AdReconciliation>  $records
     */
    private function providerTotal(iterable $records): ?string
    {
        $total = 0;
        $any = false;

        foreach ($records as $record) {
            if ($record->provider_revenue_micros !== null) {
                $total += $record->provider_revenue_micros;
                $any = true;
            }
        }

        return $any ? Micros::toDollars($total) : null;
    }

    /**
     * @return list<array{date: string, local_usd: float, provider_usd: float|null}>
     */
    private function dailyChart(CarbonImmutable $today, int $days): array
    {
        $start = $today->subDays($days - 1);
        $appTimezone = (string) config('app.timezone');

        /** @var array<string, int> $local */
        $local = [];

        AdActivity::query()
            ->where('callback_verification_status', 'verified')
            ->where('provider_confirmed_at', '>=', $start->setTimezone($appTimezone))
            ->get(['provider_confirmed_at', 'publisher_revenue_micros'])
            ->each(function (AdActivity $activity) use (&$local, $today): void {
                $key = CarbonImmutable::parse($activity->provider_confirmed_at)->setTimezone($today->getTimezone())->toDateString();
                $local[$key] = ($local[$key] ?? 0) + $activity->publisher_revenue_micros;
            });

        $provider = AdReconciliation::query()
            ->whereDate('report_date', '>=', $start->toDateString())
            ->get()
            ->mapWithKeys(fn (AdReconciliation $record): array => [substr($record->report_date, 0, 10) => $record->provider_revenue_micros]);

        $series = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->addDays($offset)->toDateString();
            $providerMicros = $provider[$date] ?? null;

            // Chart values are display-only floats; every stored/computed amount stays integer micros.
            $series[] = [
                'date' => $date,
                'local_usd' => round(($local[$date] ?? 0) / Micros::PER_UNIT, 2),
                'provider_usd' => $providerMicros === null ? null : round($providerMicros / Micros::PER_UNIT, 2),
            ];
        }

        return $series;
    }

    /**
     * Revenue by country, from the provider's own reports only (postbacks carry no country).
     *
     * @return list<array{country: string, usd: string}>
     */
    private function countryBreakdown(CarbonImmutable $since): array
    {
        /** @var array<string, int> $totals */
        $totals = [];

        AdReconciliation::query()
            ->whereDate('report_date', '>=', $since->toDateString())
            ->whereNotNull('country_breakdown')
            ->get()
            ->each(function (AdReconciliation $record) use (&$totals): void {
                foreach ($record->country_breakdown ?? [] as $country => $micros) {
                    $totals[(string) $country] = ($totals[(string) $country] ?? 0) + (int) $micros;
                }
            });

        arsort($totals);

        return array_map(
            fn (string $country, int $micros): array => ['country' => $country, 'usd' => Micros::toDollars($micros)],
            array_keys($totals),
            $totals,
        );
    }
}
