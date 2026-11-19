<?php

namespace App\Enums;

/**
 * Result of one call to a provider while looking for an ad or survey (or while testing/reporting).
 */
enum CallOutcome: string
{
    case Success = 'success';
    case Empty = 'empty';
    case Ineligible = 'ineligible';
    case Failed = 'failed';
    case Skipped = 'skipped';

    /**
     * Whether the fallback chain should move on to the next provider.
     */
    public function shouldFallBack(): bool
    {
        return $this !== self::Success;
    }
}
