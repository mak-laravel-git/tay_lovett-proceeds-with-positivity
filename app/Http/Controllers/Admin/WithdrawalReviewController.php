<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Services\WalletLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalReviewController extends Controller
{
    public function index(): Response
    {
        $requests = WithdrawalRequest::query()
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(fn (WithdrawalRequest $withdrawal): array => [
                'id' => $withdrawal->id,
                'amount_cents' => $withdrawal->amount_cents,
                'status' => $withdrawal->status,
                'destination_label' => $withdrawal->destination_label,
                'external_reference' => $withdrawal->external_reference,
                'created_at' => $withdrawal->created_at?->toDateTimeString(),
                'user' => $withdrawal->user === null
                    ? null
                    : [
                        'id' => $withdrawal->user->id,
                        'name' => $withdrawal->user->name,
                        'email' => $withdrawal->user->email,
                    ],
            ]);

        return Inertia::render('admin/withdrawals', [
            'requests' => $requests,
            'stats' => [
                'pending' => $requests->where('status', 'pending')->count(),
                'approved' => $requests->where('status', 'approved')->count(),
                'paid' => $requests->where('status', 'paid')->count(),
                'rejected' => $requests->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function approve(WithdrawalRequest $withdrawal): RedirectResponse
    {
        abort_unless($withdrawal->status === 'pending', 422);
        $withdrawal->update(['status' => 'approved']);

        return back();
    }

    public function reject(WithdrawalRequest $withdrawal, WalletLedger $ledger): RedirectResponse
    {
        abort_unless(in_array($withdrawal->status, ['pending', 'approved'], true), 422);
        $ledger->releaseReserve($withdrawal->user, $withdrawal->amount_cents, 'withdrawal-release:'.$withdrawal->id);
        $withdrawal->update(['status' => 'rejected']);

        return back();
    }

    public function paid(Request $request, WithdrawalRequest $withdrawal): RedirectResponse
    {
        $validated = $request->validate([
            'external_reference' => ['required', 'string', 'max:120'],
        ]);

        abort_unless($withdrawal->status === 'approved', 422);

        DB::transaction(function () use ($withdrawal, $validated): void {
            $wallet = Wallet::query()->lockForUpdate()->where('user_id', $withdrawal->user_id)->firstOrFail();
            $wallet->update([
                'reserved_cents' => $wallet->reserved_cents - $withdrawal->amount_cents,
            ]);
            $withdrawal->update([
                'status' => 'paid',
                'external_reference' => $validated['external_reference'],
            ]);
        });

        return back();
    }
}
