<?php

namespace App\Services\Ads\Providers\TapResearch;

use App\Enums\CallOutcome;
use App\Models\AdProvider;
use App\Services\Ads\Contracts\AdProviderAdapter;
use App\Services\Ads\Contracts\HandlesReturn;
use App\Services\Ads\Contracts\ReconcilesRevenue;
use App\Services\Ads\Data\CallbackPayload;
use App\Services\Ads\Data\Offer;
use App\Services\Ads\Data\OfferRequest;
use App\Services\Ads\Data\OfferResult;
use App\Services\Ads\Data\ReturnInfo;
use App\Services\Ads\Data\RevenueReport;
use App\Support\Micros;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * TapResearch Survey Feed API (v4) integration.
 *
 * Offers come from `GET /campaigns`. Approval and rewards come exclusively from the signed server-to-server
 * reward postback parsed by {@see self::parseCallback()}; the browser redirect never approves anything.
 */
class TapResearchAdapter implements AdProviderAdapter, HandlesReturn, ReconcilesRevenue
{
    public const KEY = 'tapresearch';

    /** Postback payout_type for a survey reward. */
    public const PAYOUT_SURVEY = 3;

    public const OUTCOME_COMPLETE = 1;

    public const OUTCOME_DISQUALIFIED = 2;

    public const OUTCOME_OVER_QUOTA = 3;

    public function __construct(private readonly TapResearchClient $client) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'TapResearch (Survey Feed API)';
    }

    public function isConfigured(AdProvider $provider): bool
    {
        return $provider->setting('api_token') !== null && $provider->setting('api_secret') !== null;
    }

    public function fetchOffers(AdProvider $provider, OfferRequest $request): OfferResult
    {
        $token = $provider->setting('api_token');

        if ($token === null) {
            return OfferResult::failed('not_configured');
        }

        $started = hrtime(true);

        try {
            $response = $this->client->campaigns($token, $request->externalUserId, $request->ip, $request->deviceType);
        } catch (ConnectionException) {
            return OfferResult::failed('connection_error', null, $this->elapsed($started));
        } catch (Throwable) {
            return OfferResult::failed('unexpected_error', null, $this->elapsed($started));
        }

        $latency = $this->elapsed($started);

        if ($response->failed()) {
            return OfferResult::failed($this->classify($response), $response->status(), $latency);
        }

        $json = $response->json();

        if (! is_array($json)) {
            return OfferResult::failed('invalid_response', $response->status(), $latency);
        }

        $eligibility = is_array($json['player_eligibility'] ?? null) ? $json['player_eligibility'] : [];

        if (($eligibility['is_eligible'] ?? true) === false) {
            return OfferResult::ineligible('ineligible_reason_'.(string) ($eligibility['reason'] ?? 'unknown'), $response->status(), $latency);
        }

        return OfferResult::success($this->mapOffers($json['surveys'] ?? []), $response->status(), $latency);
    }

    public function entryUrl(AdProvider $provider, Offer $offer, string $activityId): string
    {
        // URL-safe Base64 JSON; TapResearch returns each key as its own (signed) postback parameter.
        $passThrough = rtrim(strtr(base64_encode((string) json_encode(['activity_id' => $activityId])), '+/', '-_'), '=');
        $separator = str_contains($offer->entryUrl, '?') ? '&' : '?';

        return $offer->entryUrl.$separator.'pass_through_values='.rawurlencode($passThrough);
    }

    public function parseCallback(AdProvider $provider, Request $request): ?CallbackPayload
    {
        // The signature covers the exact bytes TapResearch sent, so it must be checked against the raw query
        // string, never against a re-encoded or re-ordered copy.
        $rawQuery = $request->server->getString('QUERY_STRING');

        if ($rawQuery === '') {
            return null;
        }

        $secret = (string) $provider->setting('api_secret');
        $configuredToken = (string) $provider->setting('api_token');
        $providedToken = $request->query('api_token');

        $signatureValid = TapResearchSignature::verifyPostback($rawQuery, $secret);
        $tokenValid = $configuredToken !== '' && is_string($providedToken) && hash_equals($configuredToken, $providedToken);

        /** @var array<string, mixed> $params */
        $params = $request->query();

        $payoutType = $this->intOrNull($params['payout_type'] ?? null);
        $outcome = $this->intOrNull($params['survey_outcome'] ?? null);

        $activityId = $params['activity_id'] ?? null;
        $activityId = is_string($activityId) && Str::isUlid($activityId) ? strtolower($activityId) : null;

        return new CallbackPayload(
            signatureValid: $signatureValid,
            tokenValid: $tokenValid,
            transactionId: $this->stringOrNull($params['cpid'] ?? null),
            externalUserId: $this->stringOrNull($params['uid'] ?? null),
            activityId: $activityId,
            offerIdentifier: $this->stringOrNull($params['offer_identifier'] ?? null),
            payoutAmount: $this->intOrNull($params['payout_amount'] ?? null),
            payoutCurrency: $this->stringOrNull($params['payout_currency'] ?? null),
            payoutType: $payoutType,
            outcome: $outcome,
            revenueMicros: $this->revenueMicros($params['revenue'] ?? null),
            kind: $this->kind($payoutType, $outcome),
            sanitized: collect($params)->except(['sig', 'api_token'])->all(),
        );
    }

    /**
     * The post-survey redirect. Its `sech` signature is checked so a forged link cannot fake a "returned" time,
     * but even a valid one only updates the user-facing experience; approval comes from the postback alone.
     */
    public function parseReturn(AdProvider $provider, Request $request): ReturnInfo
    {
        /** @var array<string, mixed> $query */
        $query = $request->query();

        $passThrough = $query['pass_through'] ?? null;
        $activityId = null;

        if (is_string($passThrough) && $passThrough !== '') {
            $decoded = json_decode((string) base64_decode(strtr($passThrough, '-_', '+/'), false), true);
            $candidate = is_array($decoded) ? ($decoded['activity_id'] ?? null) : $passThrough;
            $activityId = is_string($candidate) && Str::isUlid($candidate) ? strtolower($candidate) : null;
        }

        $status = $query['status'] ?? null;

        return new ReturnInfo(
            activityId: $activityId,
            verified: TapResearchSignature::verifyRedirect($query, (string) $provider->setting('api_secret')),
            providerStatus: is_scalar($status) ? (string) $status : null,
        );
    }

    public function testConnection(AdProvider $provider): OfferResult
    {
        $token = $provider->setting('api_token');

        if ($token === null) {
            return OfferResult::failed('not_configured');
        }

        $started = hrtime(true);

        try {
            $response = $this->client->campaigns($token, 'connection-test', '127.0.0.1', OfferRequest::DEVICE_DESKTOP);
        } catch (ConnectionException) {
            return OfferResult::failed('connection_error', null, $this->elapsed($started));
        } catch (Throwable) {
            return OfferResult::failed('unexpected_error', null, $this->elapsed($started));
        }

        $latency = $this->elapsed($started);

        // Any 2xx proves the token is accepted, even when the test participant is ineligible or has no surveys.
        return $response->successful()
            ? new OfferResult(CallOutcome::Success, [], $response->status(), null, $latency)
            : OfferResult::failed($this->classify($response), $response->status(), $latency);
    }

    /**
     * The request range follows the documented examples (ISO-8601 with offset). The response envelope is not
     * documented, so a bare list or a single wrapping key holding a list are both accepted.
     */
    public function fetchDailyRevenue(AdProvider $provider, CarbonInterface $day): RevenueReport
    {
        $token = $provider->setting('reporting_access_token');

        if ($token === null) {
            return RevenueReport::unavailable('reporting_token_missing');
        }

        $started = hrtime(true);

        try {
            $response = $this->client->dailyStats(
                $token,
                $day->copy()->startOfDay()->toIso8601String(),
                $day->copy()->endOfDay()->toIso8601String(),
            );
        } catch (ConnectionException) {
            return RevenueReport::failed('connection_error', null, $this->elapsed($started));
        } catch (Throwable) {
            return RevenueReport::failed('unexpected_error', null, $this->elapsed($started));
        }

        $latency = $this->elapsed($started);

        if (in_array($response->status(), [401, 403], true)) {
            return RevenueReport::unavailable('reporting_access_denied', $response->status());
        }

        if ($response->failed()) {
            return RevenueReport::failed($this->classify($response), $response->status(), $latency);
        }

        $json = $response->json();

        if (! is_array($json)) {
            return RevenueReport::failed('invalid_response', $response->status(), $latency);
        }

        $date = $day->format('Y-m-d');
        $rows = [];

        foreach ($this->extractRows($json) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rowDate = $row['date'] ?? null;

            if (is_string($rowDate) && ! str_starts_with($rowDate, $date)) {
                continue;
            }

            try {
                $micros = Micros::fromDecimal($row['revenue'] ?? 0);
            } catch (InvalidArgumentException) {
                continue;
            }

            $rows[] = [
                'user' => $this->stringOrNull($row['user_identifier'] ?? null),
                'country' => $this->stringOrNull($row['country_abbr'] ?? null),
                'micros' => $micros,
            ];
        }

        return RevenueReport::ok($rows, $response->status(), $latency);
    }

    /**
     * @return list<Offer>
     */
    private function mapOffers(mixed $surveys): array
    {
        if (! is_array($surveys)) {
            return [];
        }

        $offers = [];

        foreach ($surveys as $survey) {
            if (! is_array($survey)) {
                continue;
            }

            $identifier = $this->stringOrNull($survey['identifier'] ?? null);
            $url = $this->stringOrNull($survey['offer_entry_url'] ?? null);

            if ($identifier === null || $url === null || ! str_starts_with($url, 'https://')) {
                continue;
            }

            $category = $this->stringOrNull($survey['category'] ?? null);

            $offers[] = new Offer(
                identifier: $identifier,
                entryUrl: $url,
                lengthMinutes: $this->intOrNull($survey['length_in_minutes'] ?? null),
                category: $category === 'None' ? null : $category,
                supportUrl: $this->stringOrNull($survey['support_url'] ?? null),
                isHot: filter_var($survey['is_hot_tile'] ?? false, FILTER_VALIDATE_BOOLEAN),
            );
        }

        return $offers;
    }

    /**
     * @param  array<mixed>  $json
     * @return array<mixed>
     */
    private function extractRows(array $json): array
    {
        if (array_is_list($json)) {
            return $json;
        }

        foreach (['stats', 'daily_stats', 'data', 'results', 'rows'] as $key) {
            if (isset($json[$key]) && is_array($json[$key])) {
                return $json[$key];
            }
        }

        return [];
    }

    private function kind(?int $payoutType, ?int $outcome): string
    {
        if ($payoutType !== self::PAYOUT_SURVEY) {
            return CallbackPayload::KIND_OTHER;
        }

        return match ($outcome) {
            self::OUTCOME_COMPLETE => CallbackPayload::KIND_COMPLETION,
            self::OUTCOME_DISQUALIFIED, self::OUTCOME_OVER_QUOTA => CallbackPayload::KIND_REJECTED,
            default => CallbackPayload::KIND_OTHER,
        };
    }

    private function classify(Response $response): string
    {
        return match (true) {
            $response->status() === 401 => 'invalid_credentials',
            $response->status() === 422 => 'invalid_request',
            $response->status() === 429 => 'rate_limited',
            $response->status() === 503 => 'provider_unavailable',
            $response->serverError() => 'provider_error',
            default => 'http_'.$response->status(),
        };
    }

    private function revenueMicros(mixed $value): int
    {
        try {
            return is_string($value) || is_int($value) || is_float($value) ? Micros::fromDecimal($value) : 0;
        } catch (InvalidArgumentException) {
            return 0;
        }
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function elapsed(int|float $startedNanoseconds): int
    {
        return (int) round((hrtime(true) - $startedNanoseconds) / 1_000_000);
    }
}
