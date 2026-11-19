<?php

namespace App\Services\Ads\Providers\GoogleAdSense;

use App\Models\AdProvider;
use App\Services\Ads\Contracts\AdProviderAdapter;
use App\Services\Ads\Data\CallbackPayload;
use App\Services\Ads\Data\Offer;
use App\Services\Ads\Data\OfferRequest;
use App\Services\Ads\Data\OfferResult;
use Illuminate\Http\Request;

/**
 * Placeholder for Google AdSense. It exists so the provider appears in the admin list and in the fallback
 * order, but it has no integration yet: it is never configured, so the fallback chain always skips it.
 *
 * When it is built, AdSense will present display/video units rather than a survey list, so it will get its own
 * presenter on the front end (resources/js/components/ads/providers.ts) and its own reward flow.
 */
class GoogleAdSenseAdapter implements AdProviderAdapter
{
    public const KEY = 'google_adsense';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Google AdSense (coming soon)';
    }

    public function isConfigured(AdProvider $provider): bool
    {
        return false;
    }

    public function fetchOffers(AdProvider $provider, OfferRequest $request): OfferResult
    {
        return OfferResult::failed('not_implemented');
    }

    public function entryUrl(AdProvider $provider, Offer $offer, string $activityId): string
    {
        return '';
    }

    public function parseCallback(AdProvider $provider, Request $request): ?CallbackPayload
    {
        return null;
    }

    public function testConnection(AdProvider $provider): OfferResult
    {
        return OfferResult::failed('not_implemented');
    }
}
