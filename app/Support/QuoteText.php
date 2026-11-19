<?php

namespace App\Support;

class QuoteText
{
    /**
     * Collapse a quote to a comparable form.
     */
    public static function normalize(string $text): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        return mb_strtolower($collapsed);
    }

    /**
     * Hash the normalized quote so duplicates can be rejected.
     */
    public static function hash(string $text): string
    {
        return hash('sha256', self::normalize($text));
    }

    /**
     * Determine whether the quote contains a configured denylist phrase.
     */
    public static function isDenylisted(string $text): bool
    {
        $normalized = self::normalize($text);

        foreach (config('quotes.denylist', []) as $phrase) {
            if ($phrase !== '' && str_contains($normalized, self::normalize((string) $phrase))) {
                return true;
            }
        }

        return false;
    }
}
