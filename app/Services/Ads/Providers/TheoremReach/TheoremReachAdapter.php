<?php

namespace App\Services\Ads\Providers\TheoremReach;

use App\Enums\CallOutcome;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Services\Ads\Contracts\AdProviderAdapter;
use App\Services\Ads\Data\CallbackPayload;
use App\Services\Ads\Data\Offer;
use App\Services\Ads\Data\OfferRequest;
use App\Services\Ads\Data\OfferResult;
use App\Services\Ads\ProviderComplianceGate;
use App\Support\Micros;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * TheoremReach publisher offerwall (signed entry) + server reward callback.
 *
 * Browser onReward / redirect success is never authoritative. Credits come only from
 * a verified S2S callback. debug=true is test-only. Reversals arrive on the separate
 * Event Webhook controller, not this reward callback.
 */
class TheoremReachAdapter implements AdProviderAdapter
{
    public const KEY = 'theoremreach';

    public const ENTRY_BASE = 'https://theoremreach.com/respondent_entry/direct';

    public function __construct(private readonly ProviderComplianceGate $compliance) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'TheoremReach (Surveys / Offerwall)';
    }

    public function isConfigured(AdProvider $provider): bool
    {
        return $provider->setting('api_token') !== null
            && $provider->setting('api_secret') !== null
            && filled($provider->configValue('partner_id'))
            && filled($provider->configValue('currency_name_plural'))
            && filled($provider->configValue('exchange_rate'));
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

        // A placeholder offer; the real signed URL is built in entryUrl once we have an activity id.
        return OfferResult::success([
            new Offer(
                identifier: 'theoremreach-wall',
                entryUrl: self::ENTRY_BASE,
                lengthMinutes: null,
                category: 'Surveys',
            ),
        ]);
    }

    public function entryUrl(AdProvider $provider, Offer $offer, string $activityId): string
    {
        $apiKey = (string) $provider->setting('api_token');
        $secret = (string) $provider->setting('api_secret');
        $userId = $this->resolveUserIdForActivity($activityId) ?? 'unknown';

        $params = [
            'api_key' => $apiKey,
            'user_id' => $userId,
            'transaction_id' => $activityId,
            'currency_name_plural' => (string) $provider->configValue('currency_name_plural'),
            'exchange_rate' => (string) $provider->configValue('exchange_rate'),
            'external_id' => $userId,
            'partner_id' => (string) $provider->configValue('partner_id'),
        ];

        if ($provider->configValue('sandbox_mode', false) === true) {
            $params['debug'] = 'true';
        }

        $beforeHash = self::ENTRY_BASE.'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $hash = TheoremReachSignature::signEntryUrl($beforeHash, $secret);

        return $beforeHash.'&hash='.rawurlencode($hash);
    }

    /**
     * entryUrl is called after the activity is created; pull the opaque provider user id from it.
     */
    private function resolveUserIdForActivity(string $activityId): ?string
    {
        if (! Str::isUlid($activityId)) {
            return null;
        }

        return AdActivity::query()->whereKey(strtolower($activityId))->value('provider_user_id');
    }

    public function parseCallback(AdProvider $provider, Request $request): ?CallbackPayload
    {
        /** @var array<string, mixed> $params */
        $params = $request->query();

        if ($params === []) {
            return null;
        }

        $secret = (string) $provider->setting('api_secret');
        $fullUrl = $request->fullUrl();
        $rawQuery = $request->server->getString('QUERY_STRING');
        $signatureValid = TheoremReachSignature::verifyCallbackUrl($fullUrl, $secret)
            || TheoremReachSignature::verifyCallbackQuery($rawQuery, $secret);

        // Optional dashboard-confirmed vector override for local fixtures.
        $vector = $provider->configValue('callback_hash_test_vector');
        if (is_array($vector) && isset($vector['url'], $vector['hash'], $vector['valid'])) {
            if ($fullUrl === (string) $vector['url'] || $rawQuery === (string) $vector['url']) {
                $signatureValid = (bool) $vector['valid'] && hash_equals((string) $vector['hash'], (string) ($params['hash'] ?? ''));
            }
        }

        $transactionId = $this->stringOrNull($params['transaction_id'] ?? $params['tx_id'] ?? null);
        $userId = $this->stringOrNull($params['user_id'] ?? null);
        $externalTx = $this->stringOrNull($params['external_transaction_id'] ?? null);
        $activityId = null;

        if ($externalTx !== null && Str::isUlid($externalTx)) {
            $activityId = strtolower($externalTx);
        } elseif ($transactionId !== null && Str::isUlid($transactionId)) {
            // When we set transaction_id on entry to the activity ULID.
            $activityId = strtolower($transactionId);
        }

        $isDebug = filter_var($params['debug'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $reward = $params['reward'] ?? null;
        $rewardPoints = is_numeric($reward) ? (int) abs((float) $reward) : null;

        $currency = $params['currency'] ?? $params['reward_amount_in_dollars'] ?? null;
        $revenueMicros = 0;

        try {
            if (is_string($currency) || is_int($currency) || is_float($currency)) {
                $revenueMicros = Micros::fromDecimal($currency);
            }
        } catch (InvalidArgumentException) {
            $revenueMicros = 0;
        }

        $partnerId = $this->stringOrNull($params['partner_id'] ?? null);
        $configuredPartner = (string) $provider->configValue('partner_id', '');
        $tokenValid = $configuredPartner === '' || $partnerId === null || hash_equals($configuredPartner, $partnerId);

        $isReversal = filter_var($params['reversal'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return new CallbackPayload(
            signatureValid: $signatureValid,
            tokenValid: $tokenValid,
            transactionId: $transactionId,
            externalUserId: $userId,
            activityId: $activityId,
            offerIdentifier: $this->stringOrNull($params['offer_id'] ?? $params['campaign_id'] ?? null),
            payoutAmount: $rewardPoints,
            payoutCurrency: 'points',
            payoutType: null,
            outcome: $isReversal ? 2 : 1,
            revenueMicros: $revenueMicros,
            kind: $isReversal ? CallbackPayload::KIND_REJECTED : CallbackPayload::KIND_COMPLETION,
            sanitized: collect($params)->except(['hash'])->all(),
            isTestEvent: $isDebug,
        );
    }

    public function testConnection(AdProvider $provider): OfferResult
    {
        if (! $this->isConfigured($provider)) {
            return OfferResult::failed('not_configured');
        }

        $secret = (string) $provider->setting('api_secret');
        $sample = self::ENTRY_BASE.'?api_key=test&user_id=u1&transaction_id=t1';
        $hash = TheoremReachSignature::signEntryUrl($sample, $secret);

        if ($hash === '' || ! TheoremReachSignature::verifyEntryUrl($sample.'&hash='.$hash, $secret)) {
            return OfferResult::failed('signature_self_check_failed');
        }

        $state = $this->compliance->launchState($provider, true);

        return new OfferResult(
            CallOutcome::Success,
            [],
            200,
            $state['can_launch'] ? null : ($state['reason'] ?? 'compliance_blocked'),
            0,
        );
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
