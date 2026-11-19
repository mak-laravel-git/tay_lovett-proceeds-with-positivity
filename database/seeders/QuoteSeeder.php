<?php

namespace Database\Seeders;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Support\QuoteText;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuoteSeeder extends Seeder
{
    /**
     * Example published quotes taken from the V14 prototype, so the feed has
     * content before any AI generation is connected. Safe to run repeatedly.
     */
    public function run(): void
    {
        $examples = [
            ['Motivation', 'Your present circumstances don’t determine where you can go; they merely determine where you start.'],
            ['Kindness', 'Every small act of kindness creates a ripple that travels farther than you know.'],
            ['Growth', 'You do not have to see the whole staircase. Just take the next step.'],
            ['Motivation', 'Progress grows when you give yourself permission to begin again.'],
            ['Hope', 'A little hope can illuminate even the most difficult day.'],
        ];

        $categories = [];

        foreach ($examples as $position => [$name, $text]) {
            $category = $categories[$name] ??= QuoteCategory::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );

            // DatabaseSeeder runs with WithoutModelEvents, so the model's slug hook does not
            // fire here. The closure keeps the slug unchanged for quotes that already exist.
            Quote::query()->firstOrCreate(
                ['normalized_hash' => QuoteText::hash($text)],
                fn (): array => [
                    'slug' => Quote::uniqueSlug($text),
                    'quote_category_id' => $category->id,
                    'user_id' => null,
                    'text' => $text,
                    'status' => QuoteStatus::Published,
                    // Keeps the prototype order: first example is newest.
                    'published_at' => now()->subMinutes($position),
                ],
            );
        }
    }
}
