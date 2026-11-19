<?php

namespace App\Enums;

/**
 * What kind of provider activity a transaction or reward rule is about. Only surveys exist today; new kinds
 * (video, display ads, ...) are added here and get their own reward rules without any schema change.
 */
enum ActivityType: string
{
    case Survey = 'survey';

    public function label(): string
    {
        return match ($this) {
            self::Survey => 'Survey',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => ['value' => $type->value, 'label' => $type->label()], self::cases());
    }
}
