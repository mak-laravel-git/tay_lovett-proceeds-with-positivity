<?php

namespace App\Services\Ads\Contracts;

use App\Models\AdProvider;
use App\Services\Ads\Data\ReturnInfo;
use Illuminate\Http\Request;

/**
 * Optional capability: a provider that sends the user's browser back to us after the activity.
 */
interface HandlesReturn
{
    public function parseReturn(AdProvider $provider, Request $request): ReturnInfo;
}
