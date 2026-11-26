<?php

namespace App\Enums;

enum AdDevice: string
{
    case All = 'all';
    case Mobile = 'mobile';
    case Desktop = 'desktop';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All devices',
            self::Mobile => 'Phones only',
            self::Desktop => 'Tablets and desktops',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $device): array => ['value' => $device->value, 'label' => $device->label()], self::cases());
    }
}
