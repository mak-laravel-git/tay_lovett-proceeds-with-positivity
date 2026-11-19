<?php

namespace App\Services\Ads\Contracts;

use App\Models\AdProvider;
use App\Services\Ads\Data\CallbackPayload;
use App\Services\Ads\Data\Offer;
use App\Services\Ads\Data\OfferRequest;
use App\Services\Ads\Data\OfferResult;
use Illuminate\Http\Request;

/**
 * One advertising / survey provider. Each provider keeps its own integration logic behind this contract;
 * the manager, callback processor, reward service and logs are shared and know nothing provider-specific.
 */
interface AdProviderAdapter
{
    /**
     * Stable key stored in `ad_providers.key` and on every transaction.
     */
    public function key(): string;

    public function label(): string;

    /**
     * Whether the provider's stored credentials are complete enough to make calls.
     */
    public function isConfigured(AdProvider $provider): bool;

    /**
     * Ask the provider what it can show this user. Must not throw: report failures through the result.
     */
    public function fetchOffers(AdProvider $provider, OfferRequest $request): OfferResult;

    /**
     * The URL the browser should open to start an offer, carrying our activity id so the provider echoes it
     * back in its confirmation callback.
     */
    public function entryUrl(AdProvider $provider, Offer $offer, string $activityId): string;

    /**
     * Parse and authenticate an inbound confirmation callback. Returns null when the request is not a
     * callback this adapter understands at all.
     */
    public function parseCallback(AdProvider $provider, Request $request): ?CallbackPayload;

    /**
     * Cheap live check used by the admin "Test connection" button.
     */
    public function testConnection(AdProvider $provider): OfferResult;
}
