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
        /** The opaque identifier this provider knows a user by. */
        public string $externalUserId,
    ) {}

    /**
     * The coarse device class providers target surveys by, judged from the browser's user agent.
     */
    public static function deviceTypeFor(string $userAgent): int
    {
        return match (true) {
            preg_match('/iPad|Tablet|PlayBook|Silk/i', $userAgent) === 1 => self::DEVICE_TABLET,
            preg_match('/Mobi|Android|iPhone|iPod/i', $userAgent) === 1 => self::DEVICE_PHONE,
            default => self::DEVICE_DESKTOP,
        };
    }
}
