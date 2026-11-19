<?php

namespace App\Enums;

enum RewardStatus: string
{
    case Pending = 'pending';
    case Credited = 'credited';
    case NotEligible = 'not_eligible';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Credited => 'Credited',
            self::NotEligible => 'Not eligible',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
