<?php

namespace App\Services\Ads\Providers\TheoremReach;

/**
 * TheoremReach publisher signatures.
 *
 * Entry (offerwall docs): URL-safe Base64 HMAC-SHA1 over the complete pre-hash URL.
 * Event webhook (docs): HMAC-SHA1 of the full URL up to &hash= with the secret key.
 * Reward callback hash algorithm is NOT fully published; we verify using the same
 * URL-up-to-hash HMAC-SHA1 family as the event webhook (hex or URL-safe Base64).
 * Confirm against the publisher dashboard test vector before production enable.
 */
final class TheoremReachSignature
{
    /**
     * Append a URL-safe Base64 HMAC-SHA1 hash to an entry URL that does not yet include hash=.
     */
    public static function signEntryUrl(string $urlBeforeHash, string $secret): string
    {
        $digest = hash_hmac('sha1', $urlBeforeHash, $secret, true);

        return rtrim(strtr(base64_encode($digest), '+/', '-_'), '=');
    }

    public static function verifyEntryUrl(string $fullUrl, string $secret): bool
    {
        $parts = self::splitHash($fullUrl);

        if ($parts === null) {
            return false;
        }

        return hash_equals(self::signEntryUrl($parts['prefix'], $secret), $parts['hash']);
    }

    /**
     * Reward / event callback: HMAC-SHA1 hex of the exact URL bytes before the hash parameter.
     */
    public static function signCallbackUrl(string $urlBeforeHash, string $secret): string
    {
        return hash_hmac('sha1', $urlBeforeHash, $secret);
    }

    public static function verifyCallbackUrl(string $fullUrl, string $secret): bool
    {
        $parts = self::splitHash($fullUrl);

        if ($parts === null || $secret === '') {
            return false;
        }

        $expectedHex = self::signCallbackUrl($parts['prefix'], $secret);
        $expectedB64 = self::signEntryUrl($parts['prefix'], $secret);
        $provided = $parts['hash'];

        return hash_equals(strtolower($expectedHex), strtolower($provided))
            || hash_equals($expectedB64, $provided);
    }

    /**
     * Verify using the raw QUERY_STRING (hash stripped). More stable across APP_URL / proxy differences.
     */
    public static function verifyCallbackQuery(string $rawQuery, string $secret): bool
    {
        if ($rawQuery === '' || $secret === '') {
            return false;
        }

        if (preg_match('/(?:^|&)hash=([^&]*)/', $rawQuery, $matches) !== 1) {
            return false;
        }

        $provided = rawurldecode($matches[1]);
        $prefix = preg_replace('/(?:^|&)hash=[^&]*/', '', $rawQuery) ?? '';
        $prefix = trim($prefix, '&');

        if ($prefix === '' || $provided === '') {
            return false;
        }

        $expectedHex = self::signCallbackUrl($prefix, $secret);
        $expectedB64 = self::signEntryUrl($prefix, $secret);

        return hash_equals(strtolower($expectedHex), strtolower($provided))
            || hash_equals($expectedB64, $provided);
    }

    /**
     * @return array{prefix: string, hash: string}|null
     */
    public static function splitHash(string $url): ?array
    {
        if (preg_match('/^(.*)[?&]hash=([^&]*)(.*)$/', $url, $matches) !== 1) {
            return null;
        }

        $prefix = $matches[1];
        $hash = rawurldecode($matches[2]);

        if ($prefix === '' || $hash === '') {
            return null;
        }

        return ['prefix' => $prefix, 'hash' => $hash];
    }
}
