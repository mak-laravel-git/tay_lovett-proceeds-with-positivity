<?php

namespace App\Services\Ads\Data;

use App\Enums\ActivityType;
use App\Models\User;

final readonly class OfferRequest
{
    public const DEVICE_TABLET = 0;

    public const DEVICE_PHONE = 1;

    public const DEVICE_DESKTOP = 2;

    public function __construct(
        public User $user,
        public ActivityType $type,
        public string $ip,
        public int $deviceType,
        /** The opaque identifier this provider knows the user by. */
        public string $externalUserId,
    ) {}
}
