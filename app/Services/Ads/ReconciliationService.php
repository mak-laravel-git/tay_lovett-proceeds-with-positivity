<?php

namespace App\Services\Ads;

use App\Enums\CallOutcome;
use App\Enums\ReconciliationStatus;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdProviderCall;
use App\Models\AdReconciliation;
use App\Models\AdReconciliationLine;
use App\Models\AdSetting;
use App\Services\Ads\Contracts\ReconcilesRevenue;
use App\Services\Ads\Data\RevenueReport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Compares what a provider says it earned the publisher on a day with the revenue recorded locally from its
 * confirmed callbacks. The result is purely informational: this class never touches an individual transaction,
 * its status or any reward, whatever the difference turns out to be.
 *
 * Local revenue for a day = the sum of verified callbacks whose confirmation (receipt) time falls inside that
 * calendar day in the settlement timezone.
 */
class ReconciliationService
{
    private const UNKNOWN_USER = '(unknown)';

    public function __construct(private readonly AdProviderRegistry $registry) {}

    /**
     * Reconcile one provider for one day (a date string in the settlement timezone). Safe to re-run: the
     * (provider, date) record is updated in place.
     */
    public function reconcile(AdProvider $provider, string $date): AdReconciliation
    {
        $adapter = $this->registry->get($provider->key);

        if (! $adapter instanceof ReconcilesRevenue) {
            throw new InvalidArgumentException("Provider [{$provider->key}] does not publish revenue reports.");
        }

        $settings = AdSetting::current();
        $timezone = in_array($settings->settlement_timezone, timezone_identifiers_list(), true)
            ? $settings->settlement_timezone
            : AdSetting::defaults()['settlement_timezone'];

        $day = CarbonImmutable::parse($date, $timezone)->startOfDay();
        $dayEnd = $day->addDay();
        $appTimezone = (string) config('app.timezone');

        /** @var array<string, array{micros: int, count: int}> $local */
        $local = [];

        AdActivity::query()
            ->where('provider_key', $provider->key)
            ->where('callback_verification_status', 'verified')
            ->where('provider_confirmed_at', '>=', $day->setTimezone($appTimezone))
            ->where('provider_confirmed_at', '<', $dayEnd->setTimezone($appTimezone))
            ->selectRaw('provider_user_id, SUM(publisher_revenue_micros) as micros, COUNT(*) as transactions')
            ->groupBy('provider_user_id')
            ->get()
            ->each(function (AdActivity $row) use (&$local): void {
                $key = (string) ($row->getAttribute('provider_user_id') ?? self::UNKNOWN_USER);
                $local[$key] = [
                    'micros' => (int) $row->getAttribute('micros'),
                    'count' => (int) $row->getAttribute('transactions'),
                ];
            });

        $localTotal = array_sum(array_column($local, 'micros'));
        $localCount = array_sum(array_column($local, 'count'));

        $report = $adapter->fetchDailyRevenue($provider, $day);
        $this->logCall($provider, $report);

        $existing = AdReconciliation::query()
            ->where('provider_key', $provider->key)
            ->whereDate('report_date', $day->toDateString())
            ->first();

        if ($report->state !== RevenueReport::STATE_OK) {
            return $this->recordUnreported($provider, $day, $timezone, $report, $existing, $localTotal, $localCount);
        }

        [$providerUsers, $countries, $providerTotal] = $this->aggregate($report);

        $tolerance = (int) $settings->reconciliation_tolerance_micros;
        $difference = $providerTotal - $localTotal;
        $dayIsOver = $dayEnd->lte(now());
        $withinRecheckWindow = $day->gte(CarbonImmutable::now($timezone)->startOfDay()->subDays((int) $settings->reconciliation_recheck_days));

        $status = match (true) {
            ! $dayIsOver => ReconciliationStatus::Pending,
            $report->rows === [] && $localTotal === 0 => ReconciliationStatus::Matched,
            $report->rows === [] => $withinRecheckWindow ? ReconciliationStatus::Pending : ReconciliationStatus::Discrepancy,
            abs($difference) <= max($tolerance, intdiv(count($report->rows) * $tolerance, 2)) => ReconciliationStatus::Matched,
            default => ReconciliationStatus::Discrepancy,
        };

        $lines = $this->buildLines($providerUsers, $local, $tolerance);

        return DB::transaction(function () use ($provider, $day, $timezone, $report, $existing, $providerTotal, $localTotal, $localCount, $difference, $status, $countries, $lines): AdReconciliation {
            $reconciliation = AdReconciliation::query()->updateOrCreate(
                ['provider_key' => $provider->key, 'report_date' => $day->toDateString()],
                [
                    'timezone' => $timezone,
                    'provider_revenue_micros' => $report->rows === [] ? null : $providerTotal,
                    'local_revenue_micros' => $localTotal,
                    'difference_micros' => $report->rows === [] ? null : $difference,
                    'local_transaction_count' => $localCount,
                    'status' => $status,
                    'country_breakdown' => $countries === [] ? null : $countries,
                    'flag_counts' => array_count_values(array_column($lines, 'flag')),
                    'last_error' => null,
                    'fetched_at' => now(),
                    'rechecked_count' => ($existing->rechecked_count ?? -1) + 1,
                ],
            );

            $reconciliation->lines()->delete();

            foreach ($lines as $line) {
                $reconciliation->lines()->create($line);
            }

            return $reconciliation;
        });
    }

    /**
     * The provider could not report (no token, access denied, outage). Never overwrite a good earlier result with
     * a failed re-run; only note the error. A first-ever attempt is stored so the dashboard shows why.
     */
    private function recordUnreported(AdProvider $provider, CarbonImmutable $day, string $timezone, RevenueReport $report, ?AdReconciliation $existing, int $localTotal, int $localCount): AdReconciliation
    {
        $status = $report->state === RevenueReport::STATE_UNAVAILABLE ? ReconciliationStatus::Unavailable : ReconciliationStatus::Failed;

        if ($existing !== null && in_array($existing->status, [ReconciliationStatus::Matched, ReconciliationStatus::Discrepancy], true)) {
            $existing->update([
                'last_error' => $report->error,
                'rechecked_count' => $existing->rechecked_count + 1,
            ]);

            return $existing;
        }

        return AdReconciliation::query()->updateOrCreate(
            ['provider_key' => $provider->key, 'report_date' => $day->toDateString()],
            [
                'timezone' => $timezone,
                'provider_revenue_micros' => null,
                'local_revenue_micros' => $localTotal,
                'difference_micros' => null,
                'local_transaction_count' => $localCount,
                'status' => $status,
                'last_error' => $report->error,
                'rechecked_count' => ($existing->rechecked_count ?? -1) + 1,
            ],
        );
    }

    /**
     * @return array{0: array<string, int>, 1: array<string, int>, 2: int} revenue per user, per country, and the total
     */
    private function aggregate(RevenueReport $report): array
    {
        $users = [];
        $countries = [];
        $total = 0;

        foreach ($report->rows as $row) {
            $user = $row['user'] ?? self::UNKNOWN_USER;
            $users[$user] = ($users[$user] ?? 0) + $row['micros'];
            $total += $row['micros'];

            if ($row['country'] !== null) {
                $countries[$row['country']] = ($countries[$row['country']] ?? 0) + $row['micros'];
            }
        }

        return [$users, $countries, $total];
    }

    /**
     * @param  array<string, int>  $providerUsers
     * @param  array<string, array{micros: int, count: int}>  $local
     * @return list<array{external_user_id: string, provider_revenue_micros: int, local_revenue_micros: int, difference_micros: int, flag: string}>
     */
    private function buildLines(array $providerUsers, array $local, int $tolerance): array
    {
        $lines = [];

        foreach (array_unique([...array_keys($providerUsers), ...array_keys($local)]) as $user) {
            $user = (string) $user;
            $provider = $providerUsers[$user] ?? 0;
            $recorded = $local[$user]['micros'] ?? 0;

            $flag = match (true) {
                $provider > 0 && $recorded === 0 => AdReconciliationLine::FLAG_MISSING_LOCAL,
                $provider === 0 && $recorded > 0 => AdReconciliationLine::FLAG_MISSING_PROVIDER,
                abs($provider - $recorded) <= $tolerance => AdReconciliationLine::FLAG_MATCHED,
                default => AdReconciliationLine::FLAG_AMOUNT_MISMATCH,
            };

            $lines[] = [
                'external_user_id' => $user,
                'provider_revenue_micros' => $provider,
                'local_revenue_micros' => $recorded,
                'difference_micros' => $provider - $recorded,
                'flag' => $flag,
            ];
        }

        return $lines;
    }

    private function logCall(AdProvider $provider, RevenueReport $report): void
    {
        AdProviderCall::query()->create([
            'provider_key' => $provider->key,
            'operation' => 'reporting',
            'outcome' => match ($report->state) {
                RevenueReport::STATE_OK => CallOutcome::Success,
                RevenueReport::STATE_UNAVAILABLE => CallOutcome::Skipped,
                default => CallOutcome::Failed,
            },
            'http_status' => $report->httpStatus,
            'latency_ms' => $report->latencyMs,
            'error_category' => $report->error,
        ]);
    }
}
