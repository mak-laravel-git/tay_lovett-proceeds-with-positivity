<?php

namespace App\Support;

/**
 * Removes secrets from anything that is about to be written to a log an administrator can read: provider
 * answers and activity context. Secret-looking keys are blanked and URL query strings (which carry tokens and
 * signatures) are cut off.
 */
final class Redactor
{
    private const SECRET_KEY = '/token|secret|password|passwd|api[_-]?key|authorization|signature|^sig$|credential|cookie/i';

    private const HIDDEN = '[redacted]';

    private const MAX_STRING = 500;

    /**
     * @param  array<array-key, mixed>  $context
     * @return array<array-key, mixed>
     */
    public static function context(array $context): array
    {
        $clean = [];

        foreach ($context as $key => $value) {
            $clean[$key] = match (true) {
                is_string($key) && preg_match(self::SECRET_KEY, $key) === 1 => self::HIDDEN,
                is_array($value) => self::context($value),
                is_string($value) => mb_substr(self::stripQueries($value), 0, self::MAX_STRING),
                default => $value,
            };
        }

        return $clean;
    }

    /**
     * A short, safe copy of a provider's response body.
     *
     * @param  list<string|null>  $secrets  Exact values (stored credentials) that must never appear.
     */
    public static function excerpt(string $body, array $secrets = [], int $limit = 1000): ?string
    {
        $body = trim($body);

        if ($body === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        $text = is_array($decoded)
            ? (string) json_encode(self::context($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
            : self::stripQueries(mb_scrub($body));

        foreach ($secrets as $secret) {
            if ($secret !== null && $secret !== '') {
                $text = str_replace($secret, self::HIDDEN, $text);
            }
        }

        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit).'…' : $text;
    }

    private static function stripQueries(string $value): string
    {
        return (string) preg_replace('~(https?://[^\s"\'<>?]+)\?[^\s"\'<>]*~i', '$1?'.self::HIDDEN, $value);
    }
}
