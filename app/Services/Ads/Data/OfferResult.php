<?php

namespace App\Services\Ads\Data;

use App\Enums\CallOutcome;

/**
 * What one provider returned for an offer request. `outcome` decides whether the fallback chain moves on.
 */
final readonly class OfferResult
{
    /**
     * @param  list<Offer>  $offers
     */
    public function __construct(
        public CallOutcome $outcome,
        public array $offers = [],
        public ?int $httpStatus = null,
        public ?string $errorCategory = null,
        public ?int $latencyMs = null,
    ) {}

    /**
     * @param  list<Offer>  $offers
     */
    public static function success(array $offers, ?int $httpStatus = 200, ?int $latencyMs = null): self
    {
        return new self($offers === [] ? CallOutcome::Empty : CallOutcome::Success, $offers, $httpStatus, $offers === [] ? 'no_fill' : null, $latencyMs);
    }

    public static function ineligible(string $reason, ?int $httpStatus = 200, ?int $latencyMs = null): self
    {
        return new self(CallOutcome::Ineligible, [], $httpStatus, $reason, $latencyMs);
    }

    public static function failed(string $errorCategory, ?int $httpStatus = null, ?int $latencyMs = null): self
    {
        return new self(CallOutcome::Failed, [], $httpStatus, $errorCategory, $latencyMs);
    }
}
