<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Models\AdActivity;
use App\Models\LedgerEntry;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Services\Ads\PointLedger;
use App\Services\WalletLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function wallet(Request $request): Response
    {
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['pending_cents' => 0, 'available_cents' => 0, 'reserved_cents' => 0],
        );

        return Inertia::render('wallet/index', [
            'wallet' => [
                'pending_cents' => $wallet->pending_cents,
                'available_cents' => $wallet->available_cents,
                'reserved_cents' => $wallet->reserved_cents,
            ],
            'entries' => LedgerEntry::query()->where('user_id', $request->user()->id)->latest('id')->limit(20)->get([
                'entry_type', 'amount_cents', 'description', 'created_at',
            ]),
        ]);
    }

    /**
     * The signed-in user's own survey history and points. Every query is scoped to the authenticated user; no
     * route here accepts a user or activity id, so another user's records cannot be requested.
     */
    public function earnings(Request $request, PointLedger $points): Response
    {
        $user = $request->user();

        $activities = AdActivity::query()
            ->with('consumedQuote:id,slug,text')
            ->where('user_id', $user->id)
            ->where('source', AdActivity::SOURCE_APP)
            ->latest('survey_started_at')
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AdActivity $activity): array => [
                'id' => $activity->id,
                'survey' => $activity->offer_identifier,
                'provider' => $activity->provider_key,
                'started_at' => $activity->survey_started_at?->toIso8601String(),
                'completed_at' => $activity->provider_confirmed_at?->toIso8601String(),
                'status' => $activity->status->value,
                'points' => $activity->awarded_points,
                'reward_status' => $activity->reward_status->value,
                'purpose' => $activity->purpose?->label(),
                // What a completed survey paid for: the quote it unlocked, or null while it is still unspent.
                'used_for' => $activity->consumedQuote === null ? null : [
                    'slug' => $activity->consumedQuote->slug,
                    'text' => str($activity->consumedQuote->text)->limit(80)->toString(),
                ],
                'unspent' => $activity->purpose !== null
                    && $activity->status === ActivityStatus::Approved
                    && $activity->consumed_at === null,
            ]);

        return Inertia::render('earnings/index', [
            'points' => [
                'balance' => $points->balanceFor($user),
                'lifetime_earned' => $points->lifetimeEarnedFor($user),
            ],
            'activities' => $activities,
        ]);
    }

    public function withdrawals(Request $request): Response
    {
        return Inertia::render('withdrawals/index', [
            'requests' => WithdrawalRequest::query()->where('user_id', $request->user()->id)->latest()->get(),
        ]);
    }

    public function requestWithdrawal(Request $request, WalletLedger $ledger): RedirectResponse
    {
        $validated = $request->validate([
            'amount_cents' => ['required', 'integer', 'min:1'],
            'destination_label' => ['required', 'string', 'max:80'],
        ]);

        $withdrawal = WithdrawalRequest::query()->create([
            'user_id' => $request->user()->id,
            'amount_cents' => $validated['amount_cents'],
            'destination_label' => $validated['destination_label'],
            'status' => 'pending',
        ]);

        $ledger->reserve($request->user(), $withdrawal->amount_cents, 'withdrawal:'.$withdrawal->id);

        return back();
    }
}
