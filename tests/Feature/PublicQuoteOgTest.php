<?php

use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('published quote page exposes open graph and twitter tags', function () {
    $quote = Quote::factory()->published()->create(['text' => 'Keep going, brighter days are coming.']);

    $this->get(route('quotes.public', $quote))
        ->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false)
        ->assertSee(route('quotes.og', $quote), false)
        ->assertSee('Keep going, brighter days are coming.', false);
});

test('quote urls use the slug and never the database id', function () {
    $quote = Quote::factory()->published()->create();

    expect(route('quotes.public', $quote))->toEndWith('/q/'.$quote->slug)
        ->and(route('quotes.og', $quote))->toContain('/q/'.$quote->slug.'/og.png');

    $this->get('/q/'.$quote->slug)->assertOk();
    $this->get('/q/'.$quote->id)->assertNotFound();
    $this->get('/q/'.$quote->id.'/og.png')->assertNotFound();
});

test('quote page props expose the slug but not the id', function () {
    $quote = Quote::factory()->published()->create();

    $this->get(route('quotes.public', $quote))
        ->assertInertia(fn ($page) => $page
            ->component('quotes/public')
            ->where('quote.slug', $quote->slug)
            ->missing('quote.id'));
});

test('pages without a quote still render a default open graph card', function () {
    Storage::fake('local');

    $response = $this->get(route('login'))
        ->assertOk()
        ->assertSee('property="og:type" content="website"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false)
        ->assertSee('/og/default.png', false);

    expect($response->getContent())->not->toContain('rel="canonical"');

    $image = $this->get(route('site.og'))->assertOk();
    expect($image->headers->get('Content-Type'))->toBe('image/png');

    [$width, $height] = getimagesizefromstring($image->getContent());
    expect([$width, $height])->toBe([1200, 630]);
});

test('og image is a 1200x630 png for published quotes', function () {
    Storage::fake('local');
    $quote = Quote::factory()->published()->create([
        'text' => str_repeat('A very long uplifting quote that keeps going. ', 20),
    ]);

    $response = $this->get(route('quotes.og', $quote))->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('image/png');

    [$width, $height] = getimagesizefromstring($response->getContent());
    expect([$width, $height])->toBe([1200, 630]);
});

test('unpublished quotes have no page or image', function () {
    $quote = Quote::factory()->create();

    $this->get(route('quotes.public', $quote))->assertNotFound();
    $this->get(route('quotes.og', $quote))->assertNotFound();
});
