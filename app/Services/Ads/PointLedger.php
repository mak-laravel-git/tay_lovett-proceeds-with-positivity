<?php

namespace App\Services\Ads;

use App\Models\AdActivity;
use App\Models\PointBalance;
use App\Models\PointLedgerEntry;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * The only writer of points. Entries are immutable rows keyed by a unique idempotency key, so the same reward
 * can never be recorded twice, even when a provider retries a callback concurrently.
 */
class PointLedger
{
    public function credit(User $user, int $points, string $idempotencyKey, string $description, ?AdActivity $activity = null): PointLedgerEntry
    {
        // Two attempts: the first can lose a race to create the balance row; the second then sees it.
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($user, $points, $idempotencyKey, $description, $activity): PointLedgerEntry {
                    // Lock the balance first so concurrent credits for one user serialise before the key check.
                    $balance = PointBalance::query()->lockForUpdate()->firstOrCreate(
                        ['user_id' => $user->id],
                        ['balance' => 0, 'lifetime_earned' => 0],
                    );

                    $existing = PointLedgerEntry::query()->where('idempotency_key', $idempotencyKey)->first();

                    if ($existing !== null) {
                        return $existing;
                    }

                    $entry = PointLedgerEntry::query()->create([
                        'user_id' => $user->id,
                        'activity_id' => $activity?->id,
                        'entry_type' => PointLedgerEntry::TYPE_REWARD,
                        'points' => $points,
                        'idempotency_key' => $idempotencyKey,
                        'description' => $description,
                    ]);

                    $balance->update([
                        'balance' => $balance->balance + $points,
                        'lifetime_earned' => $balance->lifetime_earned + $points,
                    ]);

                    return $entry;
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                $existing = PointLedgerEntry::query()->where('idempotency_key', $idempotencyKey)->first();

                if ($existing !== null) {
                    return $existing;
                }

                if ($attempt >= 2) {
                    throw $exception;
                }
            }
        }
    }

    public function balanceFor(User $user): int
    {
        return (int) PointBalance::query()->where('user_id', $user->id)->value('balance');
    }

    public function lifetimeEarnedFor(User $user): int
    {
        return (int) PointBalance::query()->where('user_id', $user->id)->value('lifetime_earned');
    }
}
