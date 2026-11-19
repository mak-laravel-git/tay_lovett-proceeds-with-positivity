<?php

namespace App\Services\Ads;

use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Services\Ads\Contracts\AdProviderAdapter;
use App\Services\Ads\Data\CallbackPayload;
use App\Services\Ads\Data\Offer;
use App\Services\Ads\Data\OfferRequest;
use App\Services\Ads\Data\OfferResult;
use App\Services\Ads\Data\ProcessedCallback;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LogicException;

/**
 * Local development only. A provider cannot post its confirmation to 127.0.0.1, so this feeds a pre-verified
 * callback into the same CallbackProcessor a real one goes through (de-duplication, approval, rewards). It never
 * records revenue, and it refuses to run anywhere except the `local` environment.
 */
class SimulatedCompletion
{
    public function __construct(private readonly CallbackProcessor $processor) {}

    public function complete(AdActivity $activity): ProcessedCallback
    {
        if (! app()->environment('local')) {
            throw new LogicException('Survey completion can only be simulated in the local environment.');
        }

        $provider = AdProvider::query()->where('key', $activity->provider_key)->firstOrFail();

        $payload = new CallbackPayload(
            signatureValid: true,
            tokenValid: true,
            transactionId: 'sim-'.Str::lower((string) Str::ulid()),
            externalUserId: $activity->provider_user_id,
            activityId: $activity->id,
            offerIdentifier: $activity->offer_identifier,
            payoutAmount: null,
            payoutCurrency: null,
            payoutType: null,
            outcome: 1,
            revenueMicros: 0,
            kind: CallbackPayload::KIND_COMPLETION,
            sanitized: ['simulated' => true],
        );

        return $this->processor->handle($provider, $this->adapterFor($provider, $payload), Request::create('/simulated', 'GET'));
    }

    private function adapterFor(AdProvider $provider, CallbackPayload $payload): AdProviderAdapter
    {
        return new class($provider->key, $payload) implements AdProviderAdapter
        {
            public function __construct(private readonly string $key, private readonly CallbackPayload $payload) {}

            public function key(): string
            {
                return $this->key;
            }

            public function label(): string
            {
                return 'Simulated';
            }

            public function isConfigured(AdProvider $provider): bool
            {
                return true;
            }

            public function fetchOffers(AdProvider $provider, OfferRequest $request): OfferResult
            {
                return OfferResult::failed('simulated');
            }

            public function entryUrl(AdProvider $provider, Offer $offer, string $activityId): string
            {
                return '';
            }

            public function parseCallback(AdProvider $provider, Request $request): ?CallbackPayload
            {
                return $this->payload;
            }

            public function testConnection(AdProvider $provider): OfferResult
            {
                return OfferResult::failed('simulated');
            }
        };
    }
}
