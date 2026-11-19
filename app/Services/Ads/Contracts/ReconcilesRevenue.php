<?php

namespace App\Services\Ads\Contracts;

use App\Models\AdProvider;
use App\Services\Ads\Data\RevenueReport;
use Carbon\CarbonInterface;

/**
 * Optional capability: a provider that publishes its own daily revenue figures. Reconciliation compares those
 * figures with locally recorded callbacks; it can never approve, reject or reverse an individual transaction.
 */
interface ReconcilesRevenue
{
    /**
     * The provider's reported revenue for one calendar day. Must not throw.
     *
     * @param  CarbonInterface  $day  The day in the settlement timezone (time part ignored).
     */
    public function fetchDailyRevenue(AdProvider $provider, CarbonInterface $day): RevenueReport;
}
