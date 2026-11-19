<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\RewardStatus;
use App\Models\AdActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdActivity>
 */
class AdActivityFactory extends Factory
{
    /**
     * A survey the user has just started and the provider has not confirmed yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source' => AdActivity::SOURCE_APP,
            'provider_key' => 'tapresearch',
            'provider_user_id' => 'u'.Str::lower(Str::random(23)),
            'activity_type' => ActivityType::Survey,
            'offer_identifier' => (string) fake()->numberBetween(100000, 999999),
            'status' => ActivityStatus::Started,
            'reward_status' => RewardStatus::Pending,
            'survey_loaded_at' => now()->subMinute(),
            'survey_started_at' => now(),
            'callback_verification_status' => 'none',
        ];
    }

    public function approved(int $revenueMicros = 1_320_000, int $points = 10): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ActivityStatus::Approved,
            'reward_status' => RewardStatus::Credited,
            'provider_transaction_id' => 'cpid-'.Str::lower(Str::random(12)),
            'provider_confirmed_at' => now(),
            'reward_credited_at' => now(),
            'survey_outcome' => 1,
            'payout_type' => 3,
            'publisher_revenue_micros' => $revenueMicros,
            'awarded_points' => $points,
            'callback_verification_status' => 'verified',
        ]);
    }
}
