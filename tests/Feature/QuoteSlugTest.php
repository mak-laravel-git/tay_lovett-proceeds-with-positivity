<?php

use App\Models\Quote;
use App\Support\QuoteSlug;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a slug is generated from the quote text when a quote is created', function () {
    $quote = Quote::factory()->create(['text' => 'Believe in yourself every single day!']);

    expect($quote->slug)->toMatch('/^believe-in-yourself-every-single-day-[a-z0-9]{6}$/');
});

test('quotes that start with the same words still get unique slugs', function () {
    $a = Quote::factory()->create(['text' => 'Keep going with all your heart today A']);
    $b = Quote::factory()->create(['text' => 'Keep going with all your heart today B']);

    expect($a->slug)->not->toBe($b->slug);
});

test('a slug is never purely numeric', function () {
    $quote = Quote::factory()->create(['text' => '12345 67890']);
    $symbols = Quote::factory()->create(['text' => '!!! ???']);

    expect($quote->slug)->not->toMatch('/^\d+$/')
        ->and($symbols->slug)->toStartWith('quote-');
});

test('the slug does not change when the text is edited', function () {
    $quote = Quote::factory()->create();
    $slug = $quote->slug;

    $quote->update(['text' => 'A completely different sentence now.']);

    expect($quote->fresh()->slug)->toBe($slug);
});

test('generate retries until it finds a free slug', function () {
    $calls = 0;

    $slug = QuoteSlug::generate('Hello world', function () use (&$calls): bool {
        return ++$calls < 3;
    });

    expect($calls)->toBe(3)
        ->and($slug)->toStartWith('hello-world-');
});

test('seeding through DatabaseSeeder gives every quote a slug even without model events', function () {
    // DatabaseSeeder uses WithoutModelEvents, so the model hook does not run here.
    $this->seed(DatabaseSeeder::class);

    $slugs = Quote::query()->pluck('slug', 'id');

    expect($slugs)->not->toBeEmpty()
        ->and($slugs->filter(fn ($slug) => blank($slug)))->toBeEmpty()
        ->and($slugs->unique())->toHaveCount($slugs->count());

    // Re-seeding must not rewrite slugs that were already shared.
    $this->seed(DatabaseSeeder::class);

    expect(Quote::query()->pluck('slug', 'id')->all())->toBe($slugs->all());
});

test('quotes are routed by slug', function () {
    expect((new Quote)->getRouteKeyName())->toBe('slug');
});
