<?php

namespace Tests\Support;

use App\Services\Ads\Providers\Wannads\WannadsSignature;

/**
 * Builds Wannads-shaped Reward App Postback requests for tests.
 */
final class WannadsFixtures
{
    public const API_KEY = 'test-wannads-api-key';

    public const SECRET = 'test-wannads-secret';

    /**
     * @param  array<string, string|int|null>  $overrides
     */
    public static function postbackUrl(array $overrides = [], string $secret = self::SECRET): string
    {
        $params = array_merge([
            'user_id' => 'uwannads1',
            'transaction_id' => 'w-qa-'.bin2hex(random_bytes(4)),
            'reward' => '100',
            'status' => 'credited',
            'payout' => '0.75',
            'offer_id' => 'offer-1',
        ], $overrides);

        $params = array_filter($params, fn ($value) => $value !== null);

        $userId = (string) ($params['user_id'] ?? '');
        $transactionId = (string) ($params['transaction_id'] ?? '');
        $reward = (string) ($params['reward'] ?? '');

        $params['signature'] = WannadsSignature::sign($userId, $transactionId, $reward, $secret);

        return '/api/webhooks/wannads/reward?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
