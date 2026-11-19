<?php

namespace App\Console\Commands;

use App\Enums\ReconciliationStatus;
use App\Models\AdProvider;
use App\Models\AdSetting;
use App\Services\Ads\AdProviderRegistry;
use App\Services\Ads\Providers\TapResearch\TapResearchAdapter;
use App\Services\Ads\ReconciliationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ads:reconcile-tapresearch {--date= : Day to reconcile (Y-m-d, settlement timezone). Defaults to yesterday} {--days=0 : Also re-check this many days before it, to catch delayed postbacks}')]
#[Description("Compare TapResearch's reported daily revenue with the revenue recorded from postbacks (read-only for transactions)")]
class ReconcileTapResearchRevenue extends Command
{
    public function handle(AdProviderRegistry $registry, ReconciliationService $reconciliation): int
    {
        $registry->ensureRows();

        $provider = AdProvider::query()->where('key', TapResearchAdapter::KEY)->first();

        if ($provider === null) {
            $this->error('TapResearch is not registered.');

            return self::FAILURE;
        }

        $timezone = AdSetting::timezoneOrDefault();
        $date = $this->option('date');
        $anchor = is_string($date) && $date !== ''
            ? CarbonImmutable::parse($date, $timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->subDay()->startOfDay();

        $days = max(0, (int) $this->option('days'));
        $failed = false;

        for ($offset = 0; $offset <= $days; $offset++) {
            $day = $anchor->subDays($offset)->toDateString();
            $record = $reconciliation->reconcile($provider, $day);

            $this->line(sprintf(
                '%s  %-11s provider=%s local=%d difference=%s',
                $day,
                $record->status->value,
                $record->provider_revenue_micros === null ? '—' : (string) $record->provider_revenue_micros,
                $record->local_revenue_micros,
                $record->difference_micros === null ? '—' : (string) $record->difference_micros,
            ));

            $failed = $failed || $record->status === ReconciliationStatus::Failed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
