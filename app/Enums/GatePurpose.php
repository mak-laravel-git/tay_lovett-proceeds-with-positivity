<?php

namespace App\Enums;

/**
 * What a completed ad / survey unlocks. Add a case to gate another action behind the same flow.
 */
enum GatePurpose: string
{
    case QuoteGeneration = 'quote_generation';

    public function label(): string
    {
        return match ($this) {
            self::QuoteGeneration => 'Quote generation',
        };
    }
}
