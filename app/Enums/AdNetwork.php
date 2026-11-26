<?php

namespace App\Enums;

/**
 * The Google product a placement is served by. Rewarded web ads only exist in Ad Manager.
 */
enum AdNetwork: string
{
    case AdSense = 'adsense';
    case AdManager = 'ad_manager';

    public function label(): string
    {
        return match ($this) {
            self::AdSense => 'AdSense',
            self::AdManager => 'Ad Manager',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $network): array => ['value' => $network->value, 'label' => $network->label()], self::cases());
    }
}
