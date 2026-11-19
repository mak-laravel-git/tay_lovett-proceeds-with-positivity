<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletLedger
{
    /**
     * Post an immutable ledger row and update the available balance.
     */
    public function post(User $user, int $amountCents, string $type, string $idempotencyKey, string $description): LedgerEntry
    {
        return $this->once($idempotencyKey, function () use ($user, $amountCents, $type, $idempotencyKey, $description): LedgerEntry {
            $existing = LedgerEntry::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing;
            }

            $wallet = Wallet::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['pending_cents' => 0, 'available_cents' => 0, 'reserved_cents' => 0],
            );

            $next = $wallet->available_cents + $amountCents;

            if ($next < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The wallet does not have enough available balance.',
                ]);
            }

            $wallet->update(['available_cents' => $next]);

            return LedgerEntry::query()->create([
                'user_id' => $user->id,
                'entry_type' => $type,
                'amount_cents' => $amountCents,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
            ]);
        });
    }

    /**
     * Credit pending (not withdrawable) balance. Used for sandbox revenue-share allocations that must
     * never look like settled cash until an explicit confirmation path moves them to available.
     */
    public function creditPending(User $user, int $amountCents, string $type, string $idempotencyKey, string $description): LedgerEntry
    {
        return $this->once($idempotencyKey, function () use ($user, $amountCents, $type, $idempotencyKey, $description): LedgerEntry {
            $existing = LedgerEntry::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing;
            }

            if ($amountCents < 1) {
                throw ValidationException::withMessages([
                    'amount' => 'Pending credits must be at least one cent.',
                ]);
            }

            $wallet = Wallet::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['pending_cents' => 0, 'available_cents' => 0, 'reserved_cents' => 0],
            );

            $wallet->update(['pending_cents' => $wallet->pending_cents + $amountCents]);

            return LedgerEntry::query()->create([
                'user_id' => $user->id,
                'entry_type' => $type,
                'amount_cents' => $amountCents,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
            ]);
        });
    }

    /**
     * Move available funds into a withdrawal reserve.
     */
    public function reserve(User $user, int $amountCents, string $idempotencyKey): LedgerEntry
    {
        return $this->once($idempotencyKey, function () use ($user, $amountCents, $idempotencyKey): LedgerEntry {
            $existing = LedgerEntry::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing;
            }

            $wallet = Wallet::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['pending_cents' => 0, 'available_cents' => 0, 'reserved_cents' => 0],
            );

            if ($amountCents < 1 || $wallet->available_cents < $amountCents) {
                throw ValidationException::withMessages([
                    'amount' => 'The wallet does not have enough available balance.',
                ]);
            }

            $wallet->update([
                'available_cents' => $wallet->available_cents - $amountCents,
                'reserved_cents' => $wallet->reserved_cents + $amountCents,
            ]);

            return LedgerEntry::query()->create([
                'user_id' => $user->id,
                'entry_type' => 'withdrawal_reserved',
                'amount_cents' => -$amountCents,
                'idempotency_key' => $idempotencyKey,
                'description' => 'Withdrawal reserved.',
            ]);
        });
    }

    /**
     * Return a reserved withdrawal to the available balance.
     */
    public function releaseReserve(User $user, int $amountCents, string $idempotencyKey): LedgerEntry
    {
        return $this->once($idempotencyKey, function () use ($user, $amountCents, $idempotencyKey): LedgerEntry {
            $existing = LedgerEntry::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing;
            }

            $wallet = Wallet::query()->lockForUpdate()->where('user_id', $user->id)->firstOrFail();
            $wallet->update([
                'available_cents' => $wallet->available_cents + $amountCents,
                'reserved_cents' => $wallet->reserved_cents - $amountCents,
            ]);

            return LedgerEntry::query()->create([
                'user_id' => $user->id,
                'entry_type' => 'withdrawal_released',
                'amount_cents' => $amountCents,
                'idempotency_key' => $idempotencyKey,
                'description' => 'Withdrawal reserve released.',
            ]);
        });
    }

    /**
     * Run a ledger write in a transaction. If two requests with the same idempotency key race past the existence
     * check, the unique index rejects the second; return the winner's row instead of failing, so the operation
     * stays idempotent under concurrency.
     *
     * @param  Closure(): LedgerEntry  $callback
     */
    private function once(string $idempotencyKey, Closure $callback): LedgerEntry
    {
        try {
            return DB::transaction($callback, 5);
        } catch (UniqueConstraintViolationException $exception) {
            return LedgerEntry::query()->where('idempotency_key', $idempotencyKey)->first() ?? throw $exception;
        }
    }
}
