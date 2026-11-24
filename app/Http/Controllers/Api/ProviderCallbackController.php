<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdProvider;
use App\Services\Ads\AdProviderRegistry;
use App\Services\Ads\CallbackProcessor;
use App\Services\Ads\Contracts\AcknowledgesCallback;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives the server-to-server confirmation a provider sends when a survey/offer is completed.
 * All logic lives in the provider-neutral CallbackProcessor; this only routes to the right adapter
 * and answers the provider in the format it expects (JSON by default, plain "OK" for Wannads).
 */
class ProviderCallbackController extends Controller
{
    public function __invoke(Request $request, string $provider, AdProviderRegistry $registry, CallbackProcessor $processor): Response
    {
        $adapter = $registry->get($provider);
        $row = $adapter === null ? null : AdProvider::query()->where('key', $provider)->first();

        // Unknown or never-configured providers get the same answer as a bad signature: nothing is revealed.
        if ($adapter === null || $row === null || ! $adapter->isConfigured($row)) {
            if ($adapter instanceof AcknowledgesCallback) {
                return response('ERROR', 404)->header('Content-Type', 'text/plain; charset=UTF-8');
            }

            return response()->json(['status' => 'rejected'], 404);
        }

        $processed = $processor->handle($row, $adapter, $request);

        if ($adapter instanceof AcknowledgesCallback) {
            return $adapter->acknowledge($processed);
        }

        return response()->json(['status' => $processed->result->value], $processed->httpStatus);
    }
}
