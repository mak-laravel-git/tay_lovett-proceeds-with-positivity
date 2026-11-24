<?php

namespace App\Enums;

/**
 * Lifecycle of one provider transaction (one survey attempt). Only a verified provider callback can move an
 * activity to `approved` or `rejected`; the user-facing return page can only ever move it to `returned`.
 */
enum ActivityStatus: string
{
    case Loaded = 'loaded';
    case Started = 'started';
    case Returned = 'returned';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Reversed = 'reversed';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Loaded => 'Loaded',
            self::Started => 'Started',
            self::Returned => 'Returned',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Reversed => 'Reversed',
            self::Failed => 'Failed',
            self::Expired => 'Expired',
        };
    }

    /**
     * A transaction that is still waiting for the provider to confirm it.
     */
    public function isPending(): bool
    {
        return in_array($this, [self::Loaded, self::Started, self::Returned], true);
    }

    /**
     * States a provider callback may still resolve, including a late postback after the attempt expired.
     */
    public function acceptsConfirmation(): bool
    {
        return $this->isPending() || $this === self::Expired;
    }

    /**
     * @return list<string>
     */
    public static function pendingValues(): array
    {
        return [self::Loaded->value, self::Started->value, self::Returned->value];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
