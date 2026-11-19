<?php

namespace App\Services\Ads;

use App\Models\AdActivity;
use App\Models\AdSetting;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\WalletLedger;
use App\Support\Micros;

/**
 * Option B sandbox allocator: floor(1/3) of verified publisher revenue micros → user pending wallet cents.
 *
 * Credits pending only (not available), labelled SANDBOX, and only when the admin toggle is on.
 * Does not convert Google AdSense impressions into cash — only verified callback revenue on an activity.
 */
class UserRevenueShare
{
    public const ENTRY_TYPE = 'sandbox_revenue_share';

    public function __construct(private readonly WalletLedger $wallet) {}

    public function allocateIfEnabled(AdActivity $activity): ?LedgerEntry
    {
        if (! AdSetting::current()->user_revenue_share_enabled) {
            return null;
        }

        if ($activity->user_id === null || $activity->provider_transaction_id === null) {
            return null;
        }

        if ($activity->publisher_revenue_micros < 1) {
            return null;
        }

        $user = User::query()->find($activity->user_id);

        if ($user === null) {
            return null;
        }

        $shareCents = Micros::oneThirdUserShareCents($activity->publisher_revenue_micros);

        if ($shareCents < 1) {
            return null;
        }

        $eligibleDollars = Micros::toDollars($activity->publisher_revenue_micros);
        $shareDollars = number_format($shareCents / 100, 2, '.', '');

        return $this->wallet->creditPending(
            $user,
            $shareCents,
            self::ENTRY_TYPE,
            'revenue-share:'.$activity->provider_key.':'.$activity->provider_transaction_id,
            "SANDBOX revenue share (1/3 of {$eligibleDollars} USD publisher revenue) → \${$shareDollars} pending — not withdrawable cash.",
        );
    }
}
