<?php

namespace Tests\Support;

use App\Services\Ads\Providers\TapResearch\TapResearchSignature;

/**
 * Builds TapResearch-shaped requests for tests. Signing uses the same documented algorithm the app verifies,
 * so a test postback is byte-for-byte what the provider would send.
 */
final class TapResearchFixtures
{
    public const TOKEN = 'test-api-token';

    public const SECRET = 'test-api-secret';

    /**
     * A reward postback URL (path + signed query). `sig` is always the last parameter, as documented.
     *
     * @param  array<string, string|int>  $overrides  Replaces or adds parameters; set a key to null to drop it.
     */
    public static function postbackUrl(array $overrides = [], string $secret = self::SECRET): string
    {
        $params = array_merge([
            'api_token' => self::TOKEN,
            'cpid' => 'cpid-'.bin2hex(random_bytes(6)),
            'did' => 'device-1',
            'ip_address' => '203.0.113.9',
            'offer_identifier' => '555001',
            'payout_amount' => 50,
            'payout_currency' => 'points',
            'payout_type' => 3,
            'revenue' => '1.32',
            'survey_outcome' => 1,
            'tid' => 'click-1',
            'uid' => 'uexample',
        ], $overrides);

        $params = array_filter($params, fn ($value) => $value !== null);
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return '/api/webhooks/tapresearch/reward?'.$query.'&sig='.TapResearchSignature::signPostback($query, $secret);
    }

    /**
     * A /campaigns payload with the given surveys.
     *
     * @param  list<array<string, mixed>>  $surveys
     * @return array<string, mixed>
     */
    public static function campaigns(array $surveys, bool $eligible = true, int $reason = 0): array
    {
        return [
            'surveys' => $surveys,
            'player_eligibility' => ['is_eligible' => $eligible, 'reason' => $reason, 'comment' => ''],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function survey(string $identifier = '555001', int $minutes = 6): array
    {
        return [
            'identifier' => $identifier,
            'length_in_minutes' => $minutes,
            'conversion_rate' => 66,
            'reward_amount' => 999,
            'epc' => '0.12',
            'currency_name' => 'points',
            'offer_entry_url' => 'https://surveys.example.test/enter/'.$identifier,
            'support_url' => 'https://support.example.test/'.$identifier,
            'is_hot_tile' => 1,
            'category' => 'Entertainment',
        ];
    }
}
