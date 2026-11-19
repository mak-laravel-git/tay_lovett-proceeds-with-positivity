<?php

namespace App\Services\Ads\Providers\TapResearch;

/**
 * TapResearch's two signature schemes, kept in one small pure class so they are easy to test against the
 * documented procedure.
 *
 *  - Reward postback: HMAC-MD5 over the raw query string with the trailing `&sig=...` removed and
 *    percent-decoded (literal `+` characters are kept), keyed with the API secret.
 *  - Redirect URL (`sech`): HMAC-SHA256 over the present signed values, comma-joined in the documented order.
 */
final class TapResearchSignature
{
    /**
     * Order in which the redirect URL values are signed.
     *
     * @var list<string>
     */
    public const REDIRECT_FIELDS = ['status', 'revenue', 'reward', 'tid', 'click_id', 'pass_through'];

    /**
     * Verify a reward postback from the exact raw query string TapResearch sent.
     */
    public static function verifyPostback(string $rawQuery, string $secret): bool
    {
        $position = strrpos($rawQuery, '&sig=');

        if ($position === false || $secret === '') {
            return false;
        }

        $provided = strtolower(rawurldecode(substr($rawQuery, $position + 5)));
        $payload = rawurldecode(substr($rawQuery, 0, $position));

        return $provided !== '' && hash_equals(hash_hmac('md5', $payload, $secret), $provided);
    }

    /**
     * Compute the postback signature for a query string (used by tests and local simulation).
     */
    public static function signPostback(string $queryWithoutSig, string $secret): string
    {
        return hash_hmac('md5', rawurldecode($queryWithoutSig), $secret);
    }

    /**
     * Verify the `sech` value on the post-survey redirect. The documentation does not say whether an empty value
     * (for example a blank `tid`) is part of the signed string, so both readings are accepted.
     *
     * @param  array<string, mixed>  $query  Decoded query parameters of the redirect request.
     */
    public static function verifyRedirect(array $query, string $secret): bool
    {
        $provided = $query['sech'] ?? null;

        if (! is_string($provided) || $provided === '' || $secret === '') {
            return false;
        }

        $present = [];
        $nonEmpty = [];

        foreach (self::REDIRECT_FIELDS as $field) {
            if (! array_key_exists($field, $query)) {
                continue;
            }

            $value = (string) $query[$field];
            $present[] = $value;

            if ($value !== '') {
                $nonEmpty[] = $value;
            }
        }

        foreach ([$present, $nonEmpty] as $candidate) {
            if (hash_equals(hash_hmac('sha256', implode(',', $candidate), $secret), strtolower($provided))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build the signed redirect `sech` (tests and local simulation).
     *
     * @param  array<string, string>  $values
     */
    public static function signRedirect(array $values, string $secret): string
    {
        $ordered = [];

        foreach (self::REDIRECT_FIELDS as $field) {
            if (array_key_exists($field, $values)) {
                $ordered[] = $values[$field];
            }
        }

        return hash_hmac('sha256', implode(',', $ordered), $secret);
    }
}
