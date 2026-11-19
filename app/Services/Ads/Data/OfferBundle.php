<?php

namespace App\Services\Ads\Data;

use App\Models\AdRequest;

/**
 * The outcome of the whole fallback chain for one user request.
 */
final readonly class OfferBundle
{
    /**
     * @param  list<Offer>  $offers
     */
    public function __construct(
        public AdRequest $request,
        public ?string $providerKey,
        public array $offers,
    ) {}

    public function hasOffers(): bool
    {
        return $this->offers !== [];
    }
}
