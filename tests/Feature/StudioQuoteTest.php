<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;

test('studio lists only the users approved and published quotes', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $approved = Quote::factory()->approved()->create(['user_id' => $user->id]);
    $published = Quote::factory()->published()->create(['user_id' => $user->id]);
    Quote::factory()->create(['user_id' => $user->id]);
    Quote::factory()->approved()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->get(route('studio'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('studio/index')
            ->has('quotes', 2)
            ->where('quotes', fn ($quotes) => collect($quotes)->pluck('slug')->sort()->values()->all()
                === collect([$approved->slug, $published->slug])->sort()->values()->all()));
});

test('an owner can publish an approved studio quote', function () {
    $user = User::factory()->create();
    $quote = Quote::factory()->approved()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('studio.quotes.publish', $quote))
        ->assertRedirect(route('studio'));

    expect($quote->refresh()->status)->toBe(QuoteStatus::Published)
        ->and($quote->published_at)->not->toBeNull();
});

test('a non-owner cannot publish another users quote', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $quote = Quote::factory()->approved()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->post(route('studio.quotes.publish', $quote))
        ->assertForbidden();

    expect($quote->refresh()->status)->toBe(QuoteStatus::Approved);
});

test('a denylist match cannot be published from studio', function () {
    config(['quotes.denylist' => ['banned phrase']]);
    $user = User::factory()->create();
    $quote = Quote::factory()->approved()->create([
        'user_id' => $user->id,
        'text' => 'This has a banned phrase inside.',
    ]);

    $this->actingAs($user)
        ->from(route('studio'))
        ->post(route('studio.quotes.publish', $quote))
        ->assertSessionHasErrors('quote');

    expect($quote->refresh()->status)->toBe(QuoteStatus::PendingReview);
});
