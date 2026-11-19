<?php

namespace App\Services;

use App\Models\AiBudgetCounter;
use App\Models\AiBudgetReservation;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiBudget
{
    /**
     * Reserve the per-request ceiling or refuse without moving to another provider.
     */
    public function reserve(int $ceilingCents): ?AiBudgetReservation
    {
        if ($ceilingCents < 1) {
            return null;
        }

        return Cache::lock('ai:budget:'.today()->toDateString(), 10)->block(5, function () use ($ceilingCents): ?AiBudgetReservation {
            return DB::transaction(function () use ($ceilingCents): ?AiBudgetReservation {
                $settings = AiSetting::current();
                $counter = AiBudgetCounter::query()->lockForUpdate()->firstOrCreate(
                    ['usage_date' => today()->toDateString()],
                    ['reserved_cents' => 0, 'spent_cents' => 0],
                );

                $used = $counter->reserved_cents + $counter->spent_cents;

                if ($used + $ceilingCents > $settings->daily_budget_cents) {
                    return null;
                }

                $counter->increment('reserved_cents', $ceilingCents);

                return AiBudgetReservation::query()->create([
                    'ai_budget_counter_id' => $counter->id,
                    'idempotency_key' => (string) Str::uuid(),
                    'amount_cents' => $ceilingCents,
                    'state' => 'reserved',
                ]);
            });
        });
    }

    /**
     * Keep the reservation as the estimated spend. Missing token counts do not refund it.
     */
    public function spend(AiBudgetReservation $reservation, ?int $inputTokens, ?int $outputTokens): void
    {
        DB::transaction(function () use ($reservation, $inputTokens, $outputTokens): void {
            $reservation->refresh();

            if ($reservation->state !== 'reserved') {
                return;
            }

            $counter = AiBudgetCounter::query()->lockForUpdate()->findOrFail($reservation->ai_budget_counter_id);
            $counter->decrement('reserved_cents', $reservation->amount_cents);
            $counter->increment('spent_cents', $reservation->amount_cents);
            $reservation->update(['state' => 'spent']);

            if ($inputTokens === null || $outputTokens === null) {
                $reservation->update(['state' => 'spent']);
            }
        });
    }

    /**
     * Release a reservation only when no provider call was made.
     */
    public function release(AiBudgetReservation $reservation): void
    {
        DB::transaction(function () use ($reservation): void {
            $reservation->refresh();

            if ($reservation->state !== 'reserved') {
                return;
            }

            $counter = AiBudgetCounter::query()->lockForUpdate()->findOrFail($reservation->ai_budget_counter_id);
            $counter->decrement('reserved_cents', $reservation->amount_cents);
            $reservation->update(['state' => 'released']);
        });
    }
}
