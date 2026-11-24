<?php

namespace App\Services\Ads\Providers\Wannads;

use App\Enums\CallbackResult;
use App\Enums\CallOutcome;
use App\Models\AdProvider;
use App\Services\Ads\Contracts\AcknowledgesCallback;
use App\Services\Ads\Contracts\AdProviderAdapter;
use App\Services\Ads\Data\CallbackPayload;
use App\Services\Ads\Data\Offer;
use App\Services\Ads\Data\OfferRequest;
use App\Services\Ads\Data\OfferResult;
use App\Services\Ads\Data\ProcessedCallback;
use App\Services\Ads\ProviderComplianceGate;
use App\Support\Micros;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wannads HTML offerwall + Reward App Postback.
 *
 * Launch: official earn.wannads.com wall with public apiKey + opaque userId.
 * Credit: server GET postback only; browser never awards points.
 */
class WannadsAdapter implements AcknowledgesCallback, AdProviderAdapter
{
    public const KEY = 'wannads';

    public const WALL_HOST = 'https://earn.wannads.com/wall';

    public function __construct(private readonly ProviderComplianceGate $compliance) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Wannads (Offerwall)';
    }

    public function isConfigured(AdProvider $provider): bool
    {
        // api_token = public app/API key shown in the wall URL; api_secret = postback SECRET.
        return $provider->setting('api_token') !== null && $provider->setting('api_secret') !== null;
    }

    public function fetchOffers(AdProvider $provider, OfferRequest $request): OfferResult
    {
        if (! $this->isConfigured($provider)) {
            return OfferResult::failed('not_configured');
        }

        $state = $this->compliance->launchState($provider, true);
        if (! $state['can_launch']) {
            return OfferResult::failed($state['reason'] ?? 'compliance_blocked');
        }

        $apiKey = (string) $provider->setting('api_token');
        $userId = $request->externalUserId;

        $url = self::WALL_HOST.'?'.http_build_query([
            'apiKey' => $apiKey,
            'userId' => $userId,
        ], '', '&', PHP_QUERY_RFC3986);

        return OfferResult::success([
            new Offer(
                identifier: 'wannads-wall',
                entryUrl: $url,
                lengthMinutes: null,
                category: 'Offers',
                supportUrl: null,
                isHot: false,
            ),
        ]);
    }

    public function entryUrl(AdProvider $provider, Offer $offer, string $activityId): string
    {
        // aff_sub is echoed on the postback so we can match the started activity.
        $separator = str_contains($offer->entryUrl, '?') ? '&' : '?';

        return $offer->entryUrl.$separator.http_build_query([
            'aff_sub' => $activityId,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function parseCallback(AdProvider $provider, Request $request): ?CallbackPayload
    {
        /** @var array<string, mixed> $params */
        $params = $request->query();

        $userId = $this->stringOrNull($params['user_id'] ?? null);
        $transactionId = $this->stringOrNull($params['transaction_id'] ?? null);
        $rewardRaw = $params['reward'] ?? null;
        $rewardString = is_string($rewardRaw) || is_int($rewardRaw) || is_float($rewardRaw)
            ? (string) $rewardRaw
            : null;
        $signature = $this->stringOrNull($params['signature'] ?? null);
        $statusRaw = $params['status'] ?? null;

        if ($userId === null && $transactionId === null && $rewardString === null && $signature === null) {
            return null;
        }

        $secret = (string) $provider->setting('api_secret');
        $signatureValid = $userId !== null
            && $transactionId !== null
            && $rewardString !== null
            && $signature !== null
            && WannadsSignature::verify($userId, $transactionId, $rewardString, $secret, $signature);

        $kind = $this->kind($statusRaw, $rewardString);
        $affSub = $this->stringOrNull($params['aff_sub'] ?? null);
        $activityId = is_string($affSub) && Str::isUlid($affSub) ? strtolower($affSub) : null;

        $payout = $params['payout'] ?? null;
        $revenueMicros = 0;

        try {
            if (is_string($payout) || is_int($payout) || is_float($payout)) {
                $revenueMicros = Micros::fromDecimal($payout);
            }
        } catch (InvalidArgumentException) {
            $revenueMicros = 0;
        }

        $rewardPoints = is_numeric($rewardString) ? (int) abs((float) $rewardString) : null;

        return new CallbackPayload(
            signatureValid: $signatureValid,
            tokenValid: true, // Wannads authenticates via signature + optional IP allowlist, not a separate token.
            transactionId: $transactionId,
            externalUserId: $userId,
            activityId: $activityId,
            offerIdentifier: $this->stringOrNull($params['offer_id'] ?? null),
            payoutAmount: $rewardPoints,
            payoutCurrency: 'points',
            payoutType: null,
            outcome: $kind === CallbackPayload::KIND_COMPLETION ? 1 : ($kind === CallbackPayload::KIND_REJECTED ? 2 : null),
            revenueMicros: $revenueMicros,
            kind: $kind,
            sanitized: collect($params)->except(['signature'])->all(),
            isTestEvent: filter_var($params['debug'] ?? false, FILTER_VALIDATE_BOOLEAN)
                || (
                    $provider->configValue('sandbox_mode', false) === true
                    && filter_var($params['test'] ?? false, FILTER_VALIDATE_BOOLEAN)
                ),
        );
    }

    public function testConnection(AdProvider $provider): OfferResult
    {
        if (! $this->isConfigured($provider)) {
            return OfferResult::failed('not_configured');
        }

        $apiKey = (string) $provider->setting('api_token');
        $url = self::WALL_HOST.'?'.http_build_query([
            'apiKey' => $apiKey,
            'userId' => 'connection-test',
        ], '', '&', PHP_QUERY_RFC3986);

        if (! str_starts_with($url, self::WALL_HOST.'?')) {
            return OfferResult::failed('invalid_wall_host');
        }

        // Credential shape + allowlisted host check. Live inventory requires a publisher account.
        return new OfferResult(CallOutcome::Success, [
            new Offer('wannads-wall', $url, null, 'Offers'),
        ], 200, null, 0);
    }

    public function acknowledge(ProcessedCallback $processed): Response
    {
        $ok = in_array($processed->result, [
            CallbackResult::Credited,
            CallbackResult::Recorded,
            CallbackResult::Duplicate,
            CallbackResult::Reversed,
            CallbackResult::TestOnly,
            CallbackResult::Unmatched,
            CallbackResult::UnsuccessfulOutcome,
            CallbackResult::NotRewarded,
        ], true);

        // Wannads requires the exact body OK (or 1). Any other body is treated as failure and retried.
        return response($ok ? 'OK' : 'ERROR', $ok ? 200 : ($processed->httpStatus >= 400 ? $processed->httpStatus : 400))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function kind(mixed $status, ?string $rewardString): string
    {
        $normalized = is_scalar($status) ? strtolower((string) $status) : '';

        if (in_array($normalized, ['rejected', '2'], true)) {
            return CallbackPayload::KIND_REJECTED;
        }

        if (in_array($normalized, ['credited', '1'], true)) {
            return CallbackPayload::KIND_COMPLETION;
        }

        // Some payloads omit status and encode rejection as a negative reward.
        if ($rewardString !== null && is_numeric($rewardString) && (float) $rewardString < 0) {
            return CallbackPayload::KIND_REJECTED;
        }

        if ($normalized === '') {
            return CallbackPayload::KIND_OTHER;
        }

        return CallbackPayload::KIND_OTHER;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
