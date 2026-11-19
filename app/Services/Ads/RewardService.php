<?php

namespace App\Services\Ads;

use App\Enums\RewardStatus;
use App\Models\AdActivity;
use App\Models\User;

/**
 * The single place where an activity is turned into points. It is only ever called from the verified-callback
 * pipeline; no browser-facing code path reaches it.
 */
class RewardService
{
    public function __construct(
        private readonly RewardRuleResolver $rules,
        private readonly PointLedger $ledger,
    ) {}

    /**
     * Apply the configured reward to an approved activity. Safe to call twice: the ledger key is derived from
     * the provider's unique transaction id.
     */
    public function award(AdActivity $activity): RewardStatus
    {
        $user = $activity->user_id === null ? null : User::query()->find($activity->user_id);

        if ($user === null) {
            return $this->mark($activity, RewardStatus::NotEligible, 'unknown_user');
        }

        if ($activity->provider_transaction_id === null) {
            return $this->mark($activity, RewardStatus::NotEligible, 'missing_transaction_id');
        }

        $points = $this->rules->pointsFor($activity->provider_key, $activity->activity_type);

        if ($points === null) {
            return $this->mark($activity, RewardStatus::NotEligible, 'no_reward_rule');
        }

        $entry = $this->ledger->credit(
            $user,
            $points,
            "reward:{$activity->provider_key}:{$activity->provider_transaction_id}",
            ucfirst($activity->activity_type->value).' completed',
            $activity,
        );

        $activity->forceFill([
            'awarded_points' => $entry->points,
            'reward_status' => RewardStatus::Credited,
            'reward_credited_at' => $activity->reward_credited_at ?? now(),
            'reward_note' => null,
        ])->save();

        return RewardStatus::Credited;
    }

    public function decline(AdActivity $activity, string $note): RewardStatus
    {
        return $this->mark($activity, RewardStatus::NotEligible, $note);
    }

    private function mark(AdActivity $activity, RewardStatus $status, string $note): RewardStatus
    {
        $activity->forceFill(['reward_status' => $status, 'reward_note' => $note])->save();

        return $status;
    }
}
