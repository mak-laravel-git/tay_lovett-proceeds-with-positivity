<?php

namespace App\Services\Ads\Providers\TapResearch;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP layer for TapResearch. It only builds requests; mapping responses to our own types, error
 * classification and logging happen in the adapter. Credentials are passed in and never logged.
 */
class TapResearchClient
{
    /**
     * Survey Feed API: the surveys one participant currently qualifies for.
     *
     * @param  string|null  $baseUrl  An administrator's override; the configured address is used otherwise.
     * @param  int|null  $timeout  Seconds, same rule.
     *
     * @throws ConnectionException
     */
    public function campaigns(string $apiToken, string $externalUserId, string $ip, int $deviceType, ?string $baseUrl = null, ?int $timeout = null): Response
    {
        return $this->feed($apiToken, $externalUserId, $baseUrl, $timeout)->get('/campaigns', [
            'user_ip' => $ip,
            'device_type' => $deviceType,
        ]);
    }

    /**
     * Reporting API: daily roll-ups for a date range. Authenticates with its own `access_token` query parameter.
     *
     * @throws ConnectionException
     */
    public function dailyStats(string $accessToken, string $from, string $to): Response
    {
        return Http::baseUrl(rtrim((string) config('services.tapresearch.reporting_base_url'), '/'))
            ->acceptJson()
            ->timeout((int) config('services.tapresearch.timeout', 8))
            ->get('/suppliers/daily_stats', [
                'access_token' => $accessToken,
                'from' => $from,
                'to' => $to,
            ]);
    }

    private function feed(string $apiToken, string $externalUserId, ?string $baseUrl, ?int $timeout): PendingRequest
    {
        return Http::baseUrl(rtrim($baseUrl ?? (string) config('services.tapresearch.base_url'), '/'))
            ->acceptJson()
            ->timeout($timeout ?? (int) config('services.tapresearch.timeout', 8))
            // A redirect would carry the API token to wherever it points.
            ->withoutRedirecting()
            ->withHeaders([
                'X-Api-Token' => $apiToken,
                'X-User-Identifier' => $externalUserId,
            ]);
    }
}
