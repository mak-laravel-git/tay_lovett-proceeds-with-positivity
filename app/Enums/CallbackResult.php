<?php

namespace App\Enums;

/**
 * What happened to one inbound provider callback (postback). Every callback is logged with one of these.
 */
enum CallbackResult: string
{
    case Credited = 'credited';
    case Recorded = 'recorded';
    case Duplicate = 'duplicate';
    case InvalidSignature = 'invalid_signature';
    case InvalidToken = 'invalid_token';
    case UnsuccessfulOutcome = 'unsuccessful_outcome';
    case Unmatched = 'unmatched';
    case NotRewarded = 'not_rewarded';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Credited => 'Credited',
            self::Recorded => 'Recorded',
            self::Duplicate => 'Duplicate',
            self::InvalidSignature => 'Invalid signature',
            self::InvalidToken => 'Invalid token',
            self::UnsuccessfulOutcome => 'Unsuccessful outcome',
            self::Unmatched => 'Unmatched',
            self::NotRewarded => 'Not rewarded',
            self::Error => 'Error',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $result): array => ['value' => $result->value, 'label' => $result->label()], self::cases());
    }
}
