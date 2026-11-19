<?php

namespace App\Services\Ads;

use App\Enums\ActivityType;
use App\Models\AdRewardRule;

/**
 * Decides how many points a verified completion is worth. A provider-specific rule beats the global rule; a
 * disabled rule is ignored (so a disabled provider rule falls back to the global one). The survey's advertised
 * reward amount is never consulted.
 */
class RewardRuleResolver
{
    public function pointsFor(string $providerKey, ActivityType $type): ?int
    {
        $rules = AdRewardRule::query()
            ->where('activity_type', $type->value)
            ->where('is_enabled', true)
            ->where(fn ($query) => $query->where('provider_key', $providerKey)->orWhereNull('provider_key'))
            ->get();

        $rule = $rules->firstWhere('provider_key', $providerKey) ?? $rules->firstWhere('provider_key', null);

        return $rule !== null && $rule->points > 0 ? $rule->points : null;
    }
}
