<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Support\QuoteText;
use Illuminate\Validation\ValidationException;

class QuoteModeration
{
    /**
     * Approve a quote so it can later be published.
     */
    public function approve(Quote $quote): void
    {
        $quote->update([
            'status' => QuoteStatus::Approved,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject a quote with a reason an administrator can review later.
     */
    public function reject(Quote $quote, string $reason): void
    {
        $quote->update([
            'status' => QuoteStatus::Rejected,
            'rejection_reason' => $reason,
            'published_at' => null,
        ]);
    }

    /**
     * Publish an approved quote. A denylist match can only return it to review.
     */
    public function publish(Quote $quote): void
    {
        if ($quote->status !== QuoteStatus::Approved) {
            throw ValidationException::withMessages([
                'quote' => 'Only an approved quote can be published.',
            ]);
        }

        if (QuoteText::isDenylisted($quote->text)) {
            $quote->update([
                'status' => QuoteStatus::PendingReview,
                'published_at' => null,
            ]);

            throw ValidationException::withMessages([
                'quote' => 'This quote must stay in review.',
            ]);
        }

        $quote->update([
            'status' => QuoteStatus::Published,
            'published_at' => now(),
        ]);
    }

    /**
     * Return a published quote to the approved state without deleting it.
     */
    public function unpublish(Quote $quote): void
    {
        $quote->update([
            'status' => QuoteStatus::Approved,
            'published_at' => null,
        ]);
    }
}
