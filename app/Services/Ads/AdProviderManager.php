<?php

namespace App\Services\Ads;

use App\Enums\ActivityType;
use App\Enums\CallOutcome;
use App\Models\AdProvider;
use App\Models\AdProviderCall;
use App\Models\AdRequest;
use App\Models\AdSetting;
use App\Models\User;
use App\Services\Ads\Data\Offer;
use App\Services\Ads\Data\OfferBundle;
use App\Services\Ads\Data\OfferRequest;
use App\Services\Ads\Data\OfferResult;
use Illuminate\Support\Facades\Cache;

/**
 * Runs the fallback chain: enabled providers are asked in priority order and the first one that returns
 * something to show wins. A provider that fails, has nothing, or deems the user ineligible hands over to the
 * next one. Every call is logged, so the chain can be inspected afterwards.
 */
class AdProviderManager
{
    /** Consecutive failures before a provider is skipped for a short cooldown. */
    private const FAILURE_THRESHOLD = 5;

    private const COOLDOWN_SECONDS = 120;

    public function __construct(
        private readonly AdProviderRegistry $registry,
        private readonly ProviderIdentities $identities,
        private readonly ProviderComplianceGate $compliance,
    ) {}

    /**
     * @param  list<string>|null  $onlyKeys  Limit the chain to these providers (still in priority order).
     */
    public function requestOffers(User $user, ActivityType $type, string $ip, int $deviceType, ?array $onlyKeys = null): OfferBundle
    {
        return $this->runChain($user, $type, $ip, $deviceType, $onlyKeys);
    }

    /**
     * Ask a single enabled provider (user picked a card). Skips the fallback chain.
     */
    public function requestOffersFrom(User $user, string $providerKey, ActivityType $type, string $ip, int $deviceType): OfferBundle
    {
        return $this->runChain($user, $type, $ip, $deviceType, [$providerKey]);
    }

    /**
     * @param  list<string>|null  $onlyKeys
     */
    private function runChain(User $user, ActivityType $type, string $ip, int $deviceType, ?array $onlyKeys): OfferBundle
    {
        $request = AdRequest::query()->create([
            'user_id' => $user->id,
            'activity_type' => $type->value,
            'outcome' => 'no_fill',
        ]);

        $position = 0;
        $winner = null;
        $offers = [];

        $providers = AdProvider::query()->inFallbackOrder()->get();

        if ($onlyKeys !== null) {
            $providers = $providers->whereIn('key', $onlyKeys)->values();
        }

        foreach ($providers as $provider) {
            $position++;
            $adapter = $this->registry->get($provider->key);

            if ($adapter === null) {
                $this->record($request, $provider->key, 'fetch_offers', OfferResult::failed('not_registered'), $position, CallOutcome::Skipped);

                continue;
            }

            // Google AdSense is standard ads only — never part of the rewarded survey/offer chain.
            if ($provider->key === 'google_adsense') {
                $this->record($request, $provider->key, 'fetch_offers', OfferResult::failed('not_reward_provider'), $position, CallOutcome::Skipped);

                continue;
            }

            if (! $adapter->isConfigured($provider)) {
                $this->record($request, $provider->key, 'fetch_offers', OfferResult::failed('not_configured'), $position, CallOutcome::Skipped);

                continue;
            }

            // A provider the user is not allowed to launch must not win the chain: starting one of its offers
            // would be refused, and the providers after it would never be asked.
            if (! $this->compliance->launchState($provider, true)['can_launch']) {
                $this->record($request, $provider->key, 'fetch_offers', OfferResult::failed('compliance_blocked'), $position, CallOutcome::Skipped);

                continue;
            }

            if (Cache::has($this->cooldownKey($provider))) {
                $this->record($request, $provider->key, 'fetch_offers', OfferResult::failed('cooling_down'), $position, CallOutcome::Skipped);

                continue;
            }

            $result = $adapter->fetchOffers($provider, new OfferRequest(
                user: $user,
                type: $type,
                ip: $ip,
                deviceType: $deviceType,
                externalUserId: $this->identities->externalIdFor($user, $provider->key),
            ));

            $this->record($request, $provider->key, 'fetch_offers', $result, $position);
            $this->updateHealth($provider, $result);

            if ($result->outcome === CallOutcome::Success) {
                $winner = $provider->key;
                $offers = $result->offers;

                break;
            }
        }

        $request->update([
            'resolved_provider_key' => $winner,
            'outcome' => $winner === null ? 'no_fill' : 'success',
            'attempt_count' => $position,
            'offer_count' => count($offers),
        ]);

        if ($winner !== null) {
            Cache::put($this->offersKey($request->id), array_map($this->toArray(...), $offers), now()->addSeconds($this->offersTtl()));
        }

        return new OfferBundle($request, $winner, $offers);
    }

    /**
     * Find one offer from a previous request, only if it belongs to that user's request and is still cached.
     */
    public function findOffer(AdRequest $request, string $identifier): ?Offer
    {
        /** @var list<array{identifier: string, entry_url: string, length: int|null, category: string|null, support_url: string|null, hot: bool}>|null $stored */
        $stored = Cache::get($this->offersKey($request->id));

        foreach ($stored ?? [] as $row) {
            if ($row['identifier'] === $identifier) {
                return new Offer($row['identifier'], $row['entry_url'], $row['length'], $row['category'], $row['support_url'], $row['hot']);
            }
        }

        return null;
    }

    /**
     * Drop an offer once the user has opened it: most surveys do not allow re-entry.
     */
    public function forgetOffer(AdRequest $request, string $identifier): void
    {
        /** @var list<array<string, mixed>>|null $stored */
        $stored = Cache::get($this->offersKey($request->id));

        if ($stored === null) {
            return;
        }

        $remaining = array_values(array_filter($stored, fn (array $row): bool => $row['identifier'] !== $identifier));
        Cache::put($this->offersKey($request->id), $remaining, now()->addSeconds($this->offersTtl()));
    }

    /**
     * @return array{identifier: string, entry_url: string, length: int|null, category: string|null, support_url: string|null, hot: bool}
     */
    private function toArray(Offer $offer): array
    {
        return [
            'identifier' => $offer->identifier,
            'entry_url' => $offer->entryUrl,
            'length' => $offer->lengthMinutes,
            'category' => $offer->category,
            'support_url' => $offer->supportUrl,
            'hot' => $offer->isHot,
        ];
    }

    private function record(AdRequest $request, string $providerKey, string $operation, OfferResult $result, int $position, ?CallOutcome $outcome = null): void
    {
        AdProviderCall::query()->create([
            'ad_request_id' => $request->id,
            'provider_key' => $providerKey,
            'operation' => $operation,
            'outcome' => ($outcome ?? $result->outcome)->value,
            'http_status' => $result->httpStatus,
            'latency_ms' => $result->latencyMs,
            'error_category' => $result->errorCategory,
            'response_excerpt' => $result->responseExcerpt,
            'position' => $position,
        ]);
    }

    private function updateHealth(AdProvider $provider, OfferResult $result): void
    {
        if ($result->outcome === CallOutcome::Failed) {
            $failures = (int) Cache::get($this->failuresKey($provider), 0) + 1;
            Cache::put($this->failuresKey($provider), $failures, now()->addMinutes(10));

            if ($failures >= self::FAILURE_THRESHOLD) {
                Cache::put($this->cooldownKey($provider), true, now()->addSeconds(self::COOLDOWN_SECONDS));
                Cache::forget($this->failuresKey($provider));
            }

            $provider->update(['health' => 'unhealthy', 'last_error_category' => $result->errorCategory]);

            return;
        }

        Cache::forget($this->failuresKey($provider));
        $provider->update(['health' => 'healthy', 'last_success_at' => now(), 'last_error_category' => null]);
    }

    private function offersTtl(): int
    {
        return max(600, (int) AdSetting::current()->offers_cache_seconds);
    }

    private function offersKey(int $requestId): string
    {
        return "ads:request:{$requestId}:offers";
    }

    private function failuresKey(AdProvider $provider): string
    {
        return "ads:provider:{$provider->key}:failures";
    }

    private function cooldownKey(AdProvider $provider): string
    {
        return "ads:provider:{$provider->key}:cooldown";
    }
}
