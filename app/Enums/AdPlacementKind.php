<?php

namespace App\Enums;

enum AdPlacementKind: string
{
    case Rewarded = 'rewarded';
    case Display = 'display';

    public function label(): string
    {
        return match ($this) {
            self::Rewarded => 'Rewarded',
            self::Display => 'Regular (display)',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $kind): array => ['value' => $kind->value, 'label' => $kind->label()], self::cases());
    }
}
