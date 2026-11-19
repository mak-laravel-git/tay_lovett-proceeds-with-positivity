<?php

namespace App\Services\Ads;

use App\Enums\ActivityStatus;
use App\Enums\GatePurpose;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * What a user has done with ads and surveys, computed from the transaction records (no second copy of the
 * data to keep in sync). Always scoped to the one user it is asked about.
 */
class UserAdStats
{
    public function __construct(private readonly PointLedger $points) {}

    /**
     * @return array{
     *     surveys_started: int,
     *     surveys_completed: int,
     *     surveys_not_completed: int,
     *     points_balance: int,
     *     points_lifetime: int,
     *     quotes_unlocked: int,
     *     unlocks_available: int,
     *     last_completed_at: string|null,
     *     by_provider: list<array{provider: string, label: string, completed: int}>
     * }
     */
    public function for(User $user): array
    {
        $own = fn (): Builder => AdActivity::query()
            ->where('user_id', $user->id)
            ->where('source', AdActivity::SOURCE_APP);

        $completed = $own()->where('status', ActivityStatus::Approved->value);

        $labels = AdProvider::query()->pluck('label', 'key');

        return [
            'surveys_started' => $own()->count(),
            'surveys_completed' => (clone $completed)->count(),
            'surveys_not_completed' => $own()->where('status', ActivityStatus::Rejected->value)->count(),
            'points_balance' => $this->points->balanceFor($user),
            'points_lifetime' => $this->points->lifetimeEarnedFor($user),
            'quotes_unlocked' => $own()->whereNotNull('consumed_quote_id')->count(),
            'unlocks_available' => (clone $completed)
                ->where('purpose', GatePurpose::QuoteGeneration->value)
                ->whereNull('consumed_at')
                ->count(),
            'last_completed_at' => (clone $completed)->latest('provider_confirmed_at')->first()?->provider_confirmed_at?->toIso8601String(),
            'by_provider' => (clone $completed)
                ->selectRaw('provider_key, count(*) as total')
                ->groupBy('provider_key')
                ->orderByDesc('total')
                ->get()
                ->map(fn (AdActivity $row): array => [
                    'provider' => $row->provider_key,
                    'label' => (string) ($labels[$row->provider_key] ?? $row->provider_key),
                    'completed' => (int) $row->getAttribute('total'),
                ])
                ->all(),
        ];
    }
}
