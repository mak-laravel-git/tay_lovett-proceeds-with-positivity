<?php

namespace Tests\Support;

use App\Services\Ads\Providers\TheoremReach\TheoremReachSignature;

/**
 * Builds TheoremReach-shaped reward callback and entry URLs for tests.
 */
final class TheoremReachFixtures
{
    public const API_KEY = 'test-tr-api-key';

    public const SECRET = 'test-tr-secret';

    public const PARTNER_ID = 'partner-42';

    /**
     * @param  array<string, string|int|null>  $overrides
     */
    public static function rewardPath(array $overrides = [], string $secret = self::SECRET): string
    {
        $params = array_merge([
            'user_id' => 'utheorem1',
            'transaction_id' => 'tr-qa-'.bin2hex(random_bytes(4)),
            'reward' => '50',
            'currency' => '0.50',
            'partner_id' => self::PARTNER_ID,
        ], $overrides);

        $params = array_filter($params, fn ($value) => $value !== null);

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        // Sign the raw query (without hash) — matches TheoremReachSignature::verifyCallbackQuery.
        $hash = TheoremReachSignature::signCallbackUrl($query, $secret);

        return '/api/webhooks/theoremreach/reward?'.$query.'&hash='.rawurlencode($hash);
    }

    /**
     * @param  list<string>  $transactionIds
     */
    public static function eventPath(string $eventType, array $transactionIds, string $secret = self::SECRET): string
    {
        $query = http_build_query([
            'event_type' => $eventType,
            'enc_salt' => 'salt-'.bin2hex(random_bytes(3)),
        ], '', '&', PHP_QUERY_RFC3986);

        $hash = TheoremReachSignature::signCallbackUrl($query, $secret);

        return '/api/webhooks/theoremreach/events?'.$query.'&hash='.rawurlencode($hash);
    }
}
