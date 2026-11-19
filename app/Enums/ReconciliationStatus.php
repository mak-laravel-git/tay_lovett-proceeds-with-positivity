<?php

namespace App\Enums;

/**
 * Outcome of comparing one provider's reported daily revenue with the revenue recorded locally from postbacks.
 * None of these states ever changes an individual transaction or reward.
 */
enum ReconciliationStatus: string
{
    case Matched = 'matched';
    case Discrepancy = 'discrepancy';
    case Pending = 'pending';
    case Unavailable = 'unavailable';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Matched => 'Matched',
            self::Discrepancy => 'Discrepancy',
            self::Pending => 'Pending',
            self::Unavailable => 'Unavailable',
            self::Failed => 'Failed',
        };
    }
}
