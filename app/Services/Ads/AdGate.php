<?php

namespace App\Services\Ads;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\GatePurpose;
use App\Models\AdActivity;
use App\Models\AdSetting;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns a completed survey into a single-use unlock. The only thing that can make an activity count is the
 * verified provider callback (status `approved`); this class never approves anything, it only spends unlocks.
 *
 * One approved survey pays for exactly one action. Spending is a single conditional UPDATE, so two requests
 * racing for the same survey cannot both win.
 */
class AdGate
{
    public function enabled(GatePurpose $purpose): bool
    {
        return match ($purpose) {
            GatePurpose::QuoteGeneration => AdSetting::current()->gate_quote_generation,
        };
    }

    /**
     * How many unspent unlocks the user holds.
     */
    public function available(User $user, GatePurpose $purpose): int
    {
        return $this->unlocks($user, $purpose)->count();
    }

    /**
     * Spend the user's oldest unspent unlock, or return null when there is none.
     */
    public function claim(User $user, GatePurpose $purpose): ?AdActivity
    {
        $ids = $this->unlocks($user, $purpose)
            ->orderBy('provider_confirmed_at')
            ->orderBy('id')
            ->limit(5)
            ->pluck('id');

        foreach ($ids as $id) {
            $won = AdActivity::query()
                ->whereKey($id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            if ($won === 1) {
                return AdActivity::query()->find($id);
            }
        }

        return null;
    }

    /**
     * Record what the unlock paid for.
     */
    public function attach(AdActivity $activity, Quote $quote): void
    {
        $activity->forceFill(['consumed_quote_id' => $quote->id])->save();
    }

    /**
     * Give the unlock back, for example when the quote could not be generated: that is not the user's fault.
     */
    public function release(AdActivity $activity): void
    {
        $activity->forceFill(['consumed_at' => null, 'consumed_quote_id' => null])->save();
    }

    /**
     * @return Builder<AdActivity>
     */
    private function unlocks(User $user, GatePurpose $purpose): Builder
    {
        return AdActivity::query()
            ->where('user_id', $user->id)
            ->where('source', AdActivity::SOURCE_APP)
            ->where('activity_type', ActivityType::Survey->value)
            ->where('purpose', $purpose->value)
            ->where('status', ActivityStatus::Approved->value)
            ->whereNull('consumed_at');
    }
}
