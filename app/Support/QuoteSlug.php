<?php

namespace App\Support;

use Illuminate\Support\Str;

class QuoteSlug
{
    private const WORDS = 6;

    private const MAX_BASE_LENGTH = 60;

    /**
     * Build a unique, public-safe slug: the first few words of the quote plus a short
     * random suffix, e.g. "believe-in-yourself-every-day-k3x9p2".
     *
     * The suffix keeps slugs unique and unguessable. The hyphen means a slug can never
     * collide with a numeric database id.
     *
     * @param  callable(string): bool  $exists  Returns true when the slug is already taken.
     */
    public static function generate(string $text, callable $exists): string
    {
        $base = Str::slug(Str::words(trim($text), self::WORDS, ''));
        $base = trim(Str::limit($base, self::MAX_BASE_LENGTH, ''), '-');

        if ($base === '') {
            $base = 'quote';
        }

        do {
            $slug = $base.'-'.Str::lower(Str::random(6));
        } while ($exists($slug));

        return $slug;
    }
}
