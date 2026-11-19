<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Models\User;

test('a user cannot moderate quotes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.quotes'))
        ->assertForbidden();
});

test('pending and rejected quotes stay out of the feed', function () {
    $pending = Quote::factory()->create();
    $rejected = Quote::factory()->rejected()->create();
    $published = Quote::factory()->published()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('feed'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('feed/index')
            ->has('quotes', 1)
            ->where('quotes.0.slug', $published->slug)
            ->missing('quotes.0.id')
            ->missing('quotes.1'));

    expect($pending->status)->toBe(QuoteStatus::PendingReview)
        ->and($rejected->isPublished())->toBeFalse();
});

test('a rejected quote cannot be published until it is approved again', function () {
    $admin = User::factory()->administrator()->create();
    $quote = Quote::factory()->rejected()->create();

    $this->actingAs($admin)
        ->post(route('admin.quotes.publish', $quote))
        ->assertSessionHasErrors('quote');

    expect($quote->refresh()->status)->toBe(QuoteStatus::Rejected);

    $this->actingAs($admin)->post(route('admin.quotes.approve', $quote));
    $this->actingAs($admin)->post(route('admin.quotes.publish', $quote));

    expect($quote->refresh()->status)->toBe(QuoteStatus::Published);
});

test('a duplicate quote is rejected', function () {
    $admin = User::factory()->administrator()->create();
    $category = QuoteCategory::factory()->create();
    Quote::factory()->create([
        'quote_category_id' => $category->id,
        'text' => 'Keep going.',
        'normalized_hash' => hash('sha256', 'keep going.'),
    ]);

    $this->actingAs($admin)
        ->from(route('admin.quotes'))
        ->post(route('admin.quotes.store'), [
            'quote_category_id' => $category->id,
            'text' => '  Keep   going. ',
            'status' => 'published',
        ])
        ->assertSessionHasErrors('text');

    expect(Quote::query()->count())->toBe(1);
});

test('a denylist match cannot be published', function () {
    config(['quotes.denylist' => ['banned phrase']]);
    $admin = User::factory()->administrator()->create();
    $quote = Quote::factory()->approved()->create([
        'text' => 'This has a banned phrase inside.',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.quotes.publish', $quote))
        ->assertSessionHasErrors('quote');

    expect($quote->refresh()->status)->toBe(QuoteStatus::PendingReview);
});

test('a user can save a published quote and unpublish returns it to approved', function () {
    $user = User::factory()->create();
    $admin = User::factory()->administrator()->create();
    $quote = Quote::factory()->published()->create();

    $this->actingAs($user)
        ->post(route('quotes.save', $quote))
        ->assertRedirect();

    expect($user->savedQuotes()->count())->toBe(1);

    $this->actingAs($admin)->post(route('admin.quotes.unpublish', $quote));

    expect($quote->refresh()->status)->toBe(QuoteStatus::Approved);
    $this->actingAs($user)->get(route('saved'))->assertInertia(fn ($page) => $page->has('quotes', 0));
});
