<?php

namespace Tests\Support;

use App\Models\AdProvider;
use App\Services\Ads\Contracts\AdProviderAdapter;
use App\Services\Ads\Data\CallbackPayload;
use App\Services\Ads\Data\Offer;
use App\Services\Ads\Data\OfferRequest;
use App\Services\Ads\Data\OfferResult;
use Illuminate\Http\Request;

/**
 * A stand-in provider for fallback tests: returns whatever result it is given and counts how often it is asked.
 */
final class FakeAdapter implements AdProviderAdapter
{
    public int $calls = 0;

    public function __construct(
        private readonly string $key,
        private OfferResult $result,
        private readonly bool $configured = true,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return ucfirst($this->key);
    }

    public function isConfigured(AdProvider $provider): bool
    {
        return $this->configured;
    }

    public function fetchOffers(AdProvider $provider, OfferRequest $request): OfferResult
    {
        $this->calls++;

        return $this->result;
    }

    public function entryUrl(AdProvider $provider, Offer $offer, string $activityId): string
    {
        return $offer->entryUrl.'?activity='.$activityId;
    }

    public function parseCallback(AdProvider $provider, Request $request): ?CallbackPayload
    {
        return null;
    }

    public function testConnection(AdProvider $provider): OfferResult
    {
        return $this->result;
    }

    public function willReturn(OfferResult $result): void
    {
        $this->result = $result;
    }
}
