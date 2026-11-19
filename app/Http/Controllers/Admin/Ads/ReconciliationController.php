<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdProvider;
use App\Models\AdProviderPayout;
use App\Models\AdReconciliation;
use App\Models\AdReconciliationLine;
use App\Models\AdSetting;
use App\Services\Ads\AdProviderRegistry;
use App\Services\Ads\Contracts\ReconcilesRevenue;
use App\Services\Ads\ReconciliationService;
use App\Support\Micros;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/**
 * Daily comparison of provider-reported revenue and locally recorded postback revenue, plus the manual record of
 * revenue actually settled/paid. Reconciliation is informational: nothing here changes a transaction or reward.
 */
class ReconciliationController extends Controller
{
    public function index(): Response
    {
        $records = AdReconciliation::query()->orderByDesc('report_date')->orderBy('provider_key')->paginate(30)->withQueryString()
            ->through(fn (AdReconciliation $record): array => $this->present($record));

        return Inertia::render('admin/ads/reconciliation', [
            'reconciliations' => $records,
            'payouts' => AdProviderPayout::query()->with('recorder:id,name')->latest('received_at')->limit(20)->get()->map(fn (AdProviderPayout $payout): array => [
                'id' => $payout->id,
                'provider' => $payout->provider_key,
                'period_start' => $payout->period_start->toDateString(),
                'period_end' => $payout->period_end->toDateString(),
                'amount_usd' => Micros::toDollars($payout->amount_micros),
                'reference' => $payout->reference,
                'received_at' => $payout->received_at->toDateString(),
                'recorded_by' => $payout->recorder?->name,
            ])->all(),
            'providers' => AdProvider::query()->orderBy('priority')->get(['key', 'label'])->map(fn (AdProvider $provider): array => ['value' => $provider->key, 'label' => $provider->label])->all(),
            'timezone' => AdSetting::timezoneOrDefault(),
        ]);
    }

    public function show(AdReconciliation $reconciliation): Response
    {
        return Inertia::render('admin/ads/reconciliation-show', [
            'reconciliation' => $this->present($reconciliation),
            'country_breakdown' => collect($reconciliation->country_breakdown ?? [])->map(fn ($micros, $country): array => [
                'country' => (string) $country,
                'usd' => Micros::toDollars((int) $micros),
            ])->values()->all(),
            'lines' => $reconciliation->lines()->orderByRaw('flag = ? asc', [AdReconciliationLine::FLAG_MATCHED])->orderByDesc('difference_micros')->get()->map(fn (AdReconciliationLine $line): array => [
                'id' => $line->id,
                'external_user_id' => $line->external_user_id,
                'provider_usd' => Micros::toDollars($line->provider_revenue_micros),
                'local_usd' => Micros::toDollars($line->local_revenue_micros),
                'difference_usd' => Micros::toDollars($line->difference_micros),
                'flag' => $line->flag,
            ])->all(),
        ]);
    }

    /**
     * Run (or re-run) one day on demand. Re-running updates the same record; it never duplicates.
     */
    public function run(Request $request, ReconciliationService $service, AdProviderRegistry $registry): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::exists('ad_providers', 'key')],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $provider = AdProvider::query()->where('key', $validated['provider'])->firstOrFail();

        if (! $registry->get($provider->key) instanceof ReconcilesRevenue) {
            throw ValidationException::withMessages(['provider' => 'This provider does not publish revenue reports.']);
        }

        $service->reconcile($provider, $validated['date']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Reconciliation for '.$validated['date'].' updated.']);

        return back();
    }

    /**
     * Record revenue the provider actually paid. No provider API reports this, so it is entered by hand.
     */
    public function storePayout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider_key' => ['required', 'string', Rule::exists('ad_providers', 'key')],
            'period_start' => ['required', 'date_format:Y-m-d'],
            'period_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_start'],
            'amount_usd' => ['required', 'string', 'max:20'],
            'reference' => ['nullable', 'string', 'max:120'],
            'received_at' => ['required', 'date_format:Y-m-d'],
        ]);

        try {
            $micros = Micros::fromDecimal($validated['amount_usd']);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['amount_usd' => 'Enter a dollar amount such as 125.50.']);
        }

        AdProviderPayout::query()->create([
            'provider_key' => $validated['provider_key'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'amount_micros' => $micros,
            'reference' => $validated['reference'] ?? null,
            'received_at' => $validated['received_at'],
            'recorded_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payout recorded.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(AdReconciliation $record): array
    {
        return [
            'id' => $record->id,
            'provider' => $record->provider_key,
            'date' => substr($record->report_date, 0, 10),
            'timezone' => $record->timezone,
            'status' => $record->status->value,
            'provider_revenue_usd' => $record->provider_revenue_micros === null ? null : Micros::toDollars($record->provider_revenue_micros),
            'local_revenue_usd' => Micros::toDollars($record->local_revenue_micros),
            'difference_usd' => $record->difference_micros === null ? null : Micros::toDollars($record->difference_micros),
            'local_transaction_count' => $record->local_transaction_count,
            'flag_counts' => $record->flag_counts ?? [],
            'last_error' => $record->last_error,
            'fetched_at' => $record->fetched_at?->toIso8601String(),
            'rechecked_count' => $record->rechecked_count,
            'checked_at' => CarbonImmutable::parse((string) $record->updated_at)->toIso8601String(),
        ];
    }
}
