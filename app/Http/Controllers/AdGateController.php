<?php

namespace App\Http\Controllers;

use App\Enums\GatePurpose;
use App\Models\AdActivity;
use App\Services\Ads\AdGate;
use App\Services\Ads\SimulatedCompletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only status for the reusable front-end gate, plus the local-only completion simulator.
 */
class AdGateController extends Controller
{
    /**
     * Whether the signed-in user already holds an unspent unlock for this purpose.
     */
    public function show(Request $request, GatePurpose $purpose, AdGate $gate): JsonResponse
    {
        $available = $gate->available($request->user(), $purpose);

        return response()->json([
            'enabled' => $gate->enabled($purpose),
            'available' => $available,
            'unlocked' => $available > 0,
        ]);
    }

    /**
     * Local development only (the route is not registered anywhere else): complete the user's own survey the
     * way a verified provider callback would, because a provider cannot reach 127.0.0.1.
     */
    public function simulate(Request $request, AdActivity $activity, SimulatedCompletion $simulation): JsonResponse
    {
        abort_unless($activity->user_id === $request->user()->id, 404);

        $result = $simulation->complete($activity);

        return response()->json(['result' => $result->result->value]);
    }
}
