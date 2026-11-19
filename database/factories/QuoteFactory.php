<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Support\QuoteText;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $text = fake()->unique()->sentence(12);

        return [
            'quote_category_id' => QuoteCategory::factory(),
            'user_id' => null,
            'text' => $text,
            'normalized_hash' => QuoteText::hash($text),
            'status' => QuoteStatus::PendingReview,
            'rejection_reason' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuoteStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuoteStatus::Approved,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuoteStatus::Rejected,
            'rejection_reason' => 'Needs a kinder rewrite.',
        ]);
    }
}
