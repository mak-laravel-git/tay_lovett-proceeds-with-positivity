<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\Wallet;
use Inertia\Inertia;
use Inertia\Response;

class RevenueController extends Controller
{
    /**
     * Wallet and ledger truth only. Verified advertising revenue is not posted here.
     */
    public function index(): Response
    {
        $walletTotals = [
            'pending_cents' => (int) Wallet::query()->sum('pending_cents'),
            'available_cents' => (int) Wallet::query()->sum('available_cents'),
            'reserved_cents' => (int) Wallet::query()->sum('reserved_cents'),
            'wallets' => (int) Wallet::query()->count(),
        ];

        $entries = LedgerEntry::query()
            ->with('user:id,name,email')
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(fn (LedgerEntry $entry): array => [
                'id' => $entry->id,
                'entry_type' => $entry->entry_type,
                'amount_cents' => $entry->amount_cents,
                'description' => $entry->description,
                'user' => $entry->user === null
                    ? null
                    : [
                        'id' => $entry->user->id,
                        'name' => $entry->user->name,
                        'email' => $entry->user->email,
                    ],
                'created_at' => $entry->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/revenue', [
            'notice' => 'Verified advertising revenue has not been posted. This page shows wallet balances and ledger entries only — it does not estimate earnings.',
            'wallet_totals' => $walletTotals,
            'entries' => $entries,
        ]);
    }
}
