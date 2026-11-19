<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdProvider;
use App\Services\Ads\AdProviderRegistry;
use App\Services\Ads\CallbackProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives the server-to-server confirmation a provider sends when a survey is completed. All logic lives in
 * the provider-neutral CallbackProcessor; this only routes to the right adapter and answers the provider.
 */
class ProviderCallbackController extends Controller
{
    public function __invoke(Request $request, string $provider, AdProviderRegistry $registry, CallbackProcessor $processor): JsonResponse
    {
        $adapter = $registry->get($provider);
        $row = $adapter === null ? null : AdProvider::query()->where('key', $provider)->first();

        // Unknown or never-configured providers get the same answer as a bad signature: nothing is revealed.
        if ($adapter === null || $row === null || ! $adapter->isConfigured($row)) {
            return response()->json(['status' => 'rejected'], 404);
        }

        $processed = $processor->handle($row, $adapter, $request);

        return response()->json(['status' => $processed->result->value], $processed->httpStatus);
    }
}
