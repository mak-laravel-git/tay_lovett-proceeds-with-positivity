<?php

namespace App\Services;

use App\Ai\Agents\QuoteAgent;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Support\QuoteText;
use Illuminate\Validation\ValidationException;

class QuoteGenerator
{
    /**
     * Generate one quote and store it for review. The caller never receives a published row.
     */
    public function generate(QuoteCategory $category): Quote
    {
        $response = (new QuoteAgent($category->name))->prompt(
            'Write a quote about '.$category->name.'.',
        );

        $text = trim((string) ($response['text'] ?? ''));

        if ($text === '') {
            throw ValidationException::withMessages([
                'text' => 'The quote could not be generated.',
            ]);
        }

        return $this->store($category, $text);
    }

    /**
     * Store an already normalized quote. Defaults to pending review unless a status is passed.
     */
    public function store(
        QuoteCategory $category,
        string $text,
        ?int $userId = null,
        QuoteStatus $status = QuoteStatus::PendingReview,
    ): Quote {
        $text = trim($text);

        if ($text === '') {
            throw ValidationException::withMessages([
                'text' => 'The quote could not be generated.',
            ]);
        }

        $hash = QuoteText::hash($text);

        if (Quote::query()->where('normalized_hash', $hash)->exists()) {
            throw ValidationException::withMessages([
                'text' => 'This quote already exists.',
            ]);
        }

        return Quote::query()->create([
            'quote_category_id' => $category->id,
            'user_id' => $userId,
            'text' => $text,
            'normalized_hash' => $hash,
            'status' => $status,
        ]);
    }
}
