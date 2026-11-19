<?php

use App\Models\AdSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('otp:prune')->daily();

// Daily TapResearch revenue reconciliation at midnight in the configured settlement timezone. A second pass later
// in the morning catches reporting that was not ready yet; each run also re-checks the previous days so delayed
// postbacks are folded in. Both are read-only for transactions and safe to repeat.
$reconcileTimezone = AdSetting::timezoneOrDefault();
$recheckDays = (int) rescue(fn () => AdSetting::query()->value('reconciliation_recheck_days'), 3, false);

Schedule::command('ads:reconcile-tapresearch --days='.max(0, $recheckDays))
    ->dailyAt('00:05')
    ->timezone($reconcileTimezone)
    ->withoutOverlapping();

Schedule::command('ads:reconcile-tapresearch --days='.max(0, $recheckDays))
    ->dailyAt('06:05')
    ->timezone($reconcileTimezone)
    ->withoutOverlapping();
