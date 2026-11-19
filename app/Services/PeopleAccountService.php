<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PeopleAccountService
{
    /**
     * @param  array{name: string, email: string, password: string, email_verified?: bool, avatar?: UploadedFile|null, remove_avatar?: bool}  $data
     */
    public function create(UserRole $role, array $data): User
    {
        return DB::transaction(function () use ($role, $data): User {
            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $user->role = $role;
            $user->email_verified_at = ($data['email_verified'] ?? true) ? now() : null;
            $user->save();

            if ($role === UserRole::User) {
                Wallet::query()->firstOrCreate(
                    ['user_id' => $user->id],
                    ['pending_cents' => 0, 'available_cents' => 0, 'reserved_cents' => 0],
                );
            }

            if (($data['avatar'] ?? null) instanceof UploadedFile) {
                $this->storeAvatar($user, $data['avatar']);
            }

            return $user->fresh() ?? $user;
        });
    }

    /**
     * @param  array{name: string, email: string, password?: string|null, email_verified?: bool, avatar?: UploadedFile|null, remove_avatar?: bool}  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->name = $data['name'];
            $user->email = $data['email'];

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            if (array_key_exists('email_verified', $data)) {
                $user->email_verified_at = $data['email_verified'] ? ($user->email_verified_at ?? now()) : null;
            }

            if (($data['remove_avatar'] ?? false) === true) {
                $this->clearAvatar($user);
            }

            if (($data['avatar'] ?? null) instanceof UploadedFile) {
                $this->storeAvatar($user, $data['avatar']);
            }

            $user->save();

            return $user->fresh() ?? $user;
        });
    }

    public function softDelete(User $actor, User $user): void
    {
        $this->guardDestructiveAction($actor, $user);
        $user->delete();
    }

    public function restore(User $user): void
    {
        $user->restore();
    }

    public function ban(User $actor, User $user, ?string $reason): void
    {
        $this->assertModerationTarget($actor, $user);

        $user->forceFill([
            'banned_at' => now(),
            'ban_reason' => $reason,
            'suspended_until' => null,
            'suspension_reason' => null,
        ])->save();
    }

    public function unban(User $actor, User $user): void
    {
        $this->assertModerationTarget($actor, $user);

        $user->forceFill([
            'banned_at' => null,
            'ban_reason' => null,
        ])->save();
    }

    public function suspend(User $actor, User $user, int $days, ?string $reason): void
    {
        $this->assertModerationTarget($actor, $user);

        $user->forceFill([
            'suspended_until' => now()->addDays($days),
            'suspension_reason' => $reason,
            'banned_at' => null,
            'ban_reason' => null,
        ])->save();
    }

    public function unsuspend(User $actor, User $user): void
    {
        $this->assertModerationTarget($actor, $user);

        $user->forceFill([
            'suspended_until' => null,
            'suspension_reason' => null,
        ])->save();
    }

    /**
     * @return array{
     *     pending_cents: int,
     *     available_cents: int,
     *     reserved_cents: int,
     *     lifetime_credited_cents: int,
     *     recent_ledger: list<array{id: int, entry_type: string, amount_cents: int, description: string|null, created_at: string|null}>
     * }
     */
    public function walletSummary(User $user): array
    {
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['pending_cents' => 0, 'available_cents' => 0, 'reserved_cents' => 0],
        );

        $lifetimeCredited = (int) LedgerEntry::query()
            ->where('user_id', $user->id)
            ->where('amount_cents', '>', 0)
            ->sum('amount_cents');

        $recent = LedgerEntry::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(8)
            ->get(['id', 'entry_type', 'amount_cents', 'description', 'created_at'])
            ->map(fn (LedgerEntry $entry): array => [
                'id' => $entry->id,
                'entry_type' => $entry->entry_type,
                'amount_cents' => $entry->amount_cents,
                'description' => $entry->description,
                'created_at' => $entry->created_at?->toDateTimeString(),
            ])
            ->all();

        return [
            'pending_cents' => $wallet->pending_cents,
            'available_cents' => $wallet->available_cents,
            'reserved_cents' => $wallet->reserved_cents,
            'lifetime_credited_cents' => $lifetimeCredited,
            'recent_ledger' => $recent,
        ];
    }

    /**
     * Monthly earnings with previous-month comparison for progress UI.
     *
     * @return array{
     *     month: string,
     *     label: string,
     *     previous_month: string,
     *     previous_label: string,
     *     available_months: list<array{value: string, label: string}>,
     *     current: array{credited_cents: int, debited_cents: int, net_cents: int, entries: int},
     *     previous: array{credited_cents: int, debited_cents: int, net_cents: int, entries: int},
     *     change: array{credited_percent: float|null, debited_percent: float|null, net_percent: float|null, entries_percent: float|null},
     *     month_ledger: list<array{id: int, entry_type: string, amount_cents: int, description: string|null, created_at: string|null}>
     * }
     */
    public function monthlyEarnings(User $user, ?string $month = null): array
    {
        $selected = $this->resolveMonth($month);
        $previous = $selected->copy()->subMonthNoOverflow();

        $currentTotals = $this->ledgerTotalsForRange(
            $user,
            $selected->copy()->startOfMonth(),
            $selected->copy()->endOfMonth(),
        );
        $previousTotals = $this->ledgerTotalsForRange(
            $user,
            $previous->copy()->startOfMonth(),
            $previous->copy()->endOfMonth(),
        );

        $monthLedger = LedgerEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [
                $selected->copy()->startOfMonth(),
                $selected->copy()->endOfMonth(),
            ])
            ->latest('id')
            ->limit(20)
            ->get(['id', 'entry_type', 'amount_cents', 'description', 'created_at'])
            ->map(fn (LedgerEntry $entry): array => [
                'id' => $entry->id,
                'entry_type' => $entry->entry_type,
                'amount_cents' => $entry->amount_cents,
                'description' => $entry->description,
                'created_at' => $entry->created_at?->toDateTimeString(),
            ])
            ->all();

        return [
            'month' => $selected->format('Y-m'),
            'label' => $selected->format('F Y'),
            'previous_month' => $previous->format('Y-m'),
            'previous_label' => $previous->format('F Y'),
            'available_months' => $this->availableMonthOptions(),
            'current' => $currentTotals,
            'previous' => $previousTotals,
            'change' => [
                'credited_percent' => $this->percentChange(
                    $previousTotals['credited_cents'],
                    $currentTotals['credited_cents'],
                ),
                'debited_percent' => $this->percentChange(
                    $previousTotals['debited_cents'],
                    $currentTotals['debited_cents'],
                ),
                'net_percent' => $this->percentChange(
                    $previousTotals['net_cents'],
                    $currentTotals['net_cents'],
                ),
                'entries_percent' => $this->percentChange(
                    $previousTotals['entries'],
                    $currentTotals['entries'],
                ),
            ],
            'month_ledger' => $monthLedger,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(User $user, bool $withWallet = false, ?string $month = null): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role' => $user->role->value,
            'verified' => $user->email_verified_at !== null,
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'banned_at' => $user->banned_at?->toIso8601String(),
            'ban_reason' => $user->ban_reason,
            'suspended_until' => $user->suspended_until?->toIso8601String(),
            'suspension_reason' => $user->suspension_reason,
            'is_banned' => $user->isBanned(),
            'is_suspended' => $user->isSuspended(),
            'deleted_at' => $user->deleted_at?->toIso8601String(),
            'created_at' => $user->created_at?->toDateString(),
            'updated_at' => $user->updated_at?->toDateTimeString(),
            'status' => $this->statusOf($user),
        ];

        if ($withWallet && $user->role === UserRole::User) {
            $payload['wallet'] = $this->walletSummary($user);
            $payload['earnings'] = $this->monthlyEarnings($user, $month);
        }

        return $payload;
    }

    private function resolveMonth(?string $month): CarbonInterface
    {
        if ($month !== null && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return now()->createFromFormat('Y-m', $month)->startOfMonth();
        }

        return now()->startOfMonth();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function availableMonthOptions(): array
    {
        return collect(range(0, 11))
            ->map(function (int $monthsAgo): array {
                $date = now()->startOfMonth()->subMonthsNoOverflow($monthsAgo);

                return [
                    'value' => $date->format('Y-m'),
                    'label' => $date->format('F Y'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{credited_cents: int, debited_cents: int, net_cents: int, entries: int}
     */
    /**
     * @return array{credited_cents: int, debited_cents: int, net_cents: int, entries: int}
     */
    private function ledgerTotalsForRange(User $user, CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = LedgerEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->get(['amount_cents']);

        $credited = (int) $rows->where('amount_cents', '>', 0)->sum('amount_cents');
        $debited = (int) abs($rows->where('amount_cents', '<', 0)->sum('amount_cents'));
        $net = (int) $rows->sum('amount_cents');

        return [
            'credited_cents' => $credited,
            'debited_cents' => $debited,
            'net_cents' => $net,
            'entries' => $rows->count(),
        ];
    }

    private function percentChange(int $previous, int $current): ?float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    public function statusOf(User $user): string
    {
        if ($user->trashed()) {
            return 'deleted';
        }

        if ($user->isBanned()) {
            return 'banned';
        }

        if ($user->isSuspended()) {
            return 'suspended';
        }

        return 'active';
    }

    private function storeAvatar(User $user, UploadedFile $avatar): void
    {
        $this->clearAvatar($user);

        $path = $avatar->store('avatars/'.$user->id, 'public');
        $user->avatar_path = $path;
        $user->save();
    }

    private function clearAvatar(User $user): void
    {
        if ($user->avatar_path !== null && $user->avatar_path !== '') {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = null;
    }

    private function guardDestructiveAction(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account from here.',
            ]);
        }

        if ($user->isAdministrator()) {
            $remaining = User::query()
                ->where('role', UserRole::Administrator)
                ->whereKeyNot($user->id)
                ->count();

            if ($remaining === 0) {
                throw ValidationException::withMessages([
                    'user' => 'You cannot delete the last administrator.',
                ]);
            }
        }
    }

    private function assertModerationTarget(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot moderate your own account.',
            ]);
        }

        if ($user->isAdministrator()) {
            throw ValidationException::withMessages([
                'user' => 'Administrator accounts cannot be banned or suspended.',
            ]);
        }
    }
}
