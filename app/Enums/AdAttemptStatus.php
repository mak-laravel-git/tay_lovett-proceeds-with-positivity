<?php

namespace App\Enums;

/**
 * Lifecycle of one rewarded ad attempt: issued -> ready -> shown -> granted, or one of the ways it can fail.
 */
enum AdAttemptStatus: string
{
    case Issued = 'issued';
    case Ready = 'ready';
    case Shown = 'shown';
    case Granted = 'granted';
    case Dismissed = 'dismissed';
    case Unavailable = 'unavailable';
    case Error = 'error';
    case Superseded = 'superseded';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isOpen(): bool
    {
        return in_array($this->value, self::openValues(), true);
    }

    /**
     * @return list<string>
     */
    public static function openValues(): array
    {
        return [self::Issued->value, self::Ready->value, self::Shown->value];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
