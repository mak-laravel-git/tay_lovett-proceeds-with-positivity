<?php

namespace App\Services\Ads\Data;

/**
 * What the browser redirect after a survey tells us. It only ever drives the user-facing experience: nothing
 * about a ReturnInfo can approve a survey or award points.
 */
final readonly class ReturnInfo
{
    public function __construct(
        public ?string $activityId,
        public bool $verified,
        public ?string $providerStatus,
    ) {}
}
