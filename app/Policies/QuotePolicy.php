<?php

namespace App\Policies;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    /**
     * Determine whether the user may moderate quotes.
     */
    public function moderate(User $user, ?Quote $quote = null): bool
    {
        return $user->isAdministrator();
    }

    /**
     * Determine whether the user may save a published quote.
     */
    public function save(User $user, Quote $quote): bool
    {
        return $quote->isPublished();
    }

    /**
     * Determine whether the user may publish their own approved quote from Studio.
     */
    public function publish(User $user, Quote $quote): bool
    {
        return $quote->user_id === $user->id
            && $quote->status === QuoteStatus::Approved;
    }
}
