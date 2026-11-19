<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Support\QuoteSlug;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property QuoteStatus $status
 * @property Carbon|null $published_at
 */
#[Fillable([
    'slug',
    'quote_category_id',
    'user_id',
    'text',
    'normalized_hash',
    'status',
    'rejection_reason',
    'published_at',
])]
class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // Every create path (AI, admin form, seeder, factory) gets a slug here.
        // It is never regenerated, so links that were already shared keep working.
        // Model events are switched off while seeding (WithoutModelEvents), so seeders that
        // create quotes must pass `slug => Quote::uniqueSlug($text)` themselves.
        static::creating(function (Quote $quote): void {
            if (blank($quote->slug)) {
                $quote->slug = static::uniqueSlug((string) $quote->text);
            }
        });
    }

    /**
     * Build a slug for the given quote text that no existing quote is using.
     */
    public static function uniqueSlug(string $text): string
    {
        return QuoteSlug::generate(
            $text,
            fn (string $slug): bool => static::query()->where('slug', $slug)->exists(),
        );
    }

    /**
     * Public URLs and route binding use the slug, never the database id.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<QuoteCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(QuoteCategory::class, 'quote_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function savers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_quotes')->withTimestamps();
    }

    public function isPublished(): bool
    {
        return $this->status === QuoteStatus::Published;
    }
}
