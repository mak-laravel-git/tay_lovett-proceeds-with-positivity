<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Enums\CallOutcome;
use App\Http\Controllers\Controller;
use App\Models\AdProvider;
use App\Models\AdProviderCall;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Monitoring of every call made to a provider: successes, failures, latency and the fallback chain.
 */
class RequestLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'provider' => ['nullable', 'string', 'max:60'],
            'outcome' => ['nullable', 'string', 'in:'.implode(',', array_column(CallOutcome::cases(), 'value'))],
            'operation' => ['nullable', 'string', 'max:40'],
        ]);

        $query = AdProviderCall::query()
            ->when($filters['provider'] ?? null, fn (Builder $builder, string $provider) => $builder->where('provider_key', $provider))
            ->when($filters['outcome'] ?? null, fn (Builder $builder, string $outcome) => $builder->where('outcome', $outcome))
            ->when($filters['operation'] ?? null, fn (Builder $builder, string $operation) => $builder->where('operation', $operation));

        $calls = (clone $query)->with('request:id,user_id,outcome,resolved_provider_key,attempt_count')
            ->latest('id')->paginate(30)->withQueryString()
            ->through(fn (AdProviderCall $call): array => [
                'id' => $call->id,
                'at' => $call->created_at?->toIso8601String(),
                'provider' => $call->provider_key,
                'operation' => $call->operation,
                'outcome' => $call->outcome->value,
                'http_status' => $call->http_status,
                'latency_ms' => $call->latency_ms,
                'error' => $call->error_category,
                'position' => $call->position,
                'request_id' => $call->ad_request_id,
                'request_outcome' => $call->request?->outcome,
                'resolved_provider' => $call->request?->resolved_provider_key,
            ]);

        // Per-provider success rate over the last 7 days, for the monitoring header.
        $stats = AdProviderCall::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->where('outcome', '!=', CallOutcome::Skipped->value)
            ->select('provider_key', DB::raw('count(*) as total'), DB::raw("sum(case when outcome = 'success' then 1 else 0 end) as ok"), DB::raw("sum(case when outcome = 'failed' then 1 else 0 end) as failed"), DB::raw('avg(latency_ms) as avg_latency'))
            ->groupBy('provider_key')
            ->get()
            ->map(fn ($row): array => [
                'provider' => (string) $row->getAttribute('provider_key'),
                'total' => (int) $row->getAttribute('total'),
                'success' => (int) $row->getAttribute('ok'),
                'failed' => (int) $row->getAttribute('failed'),
                'avg_latency_ms' => $row->getAttribute('avg_latency') === null ? null : (int) round((float) $row->getAttribute('avg_latency')),
            ])->all();

        return Inertia::render('admin/ads/requests', [
            'calls' => $calls,
            'stats' => $stats,
            'filters' => array_filter($filters, fn ($value) => $value !== null && $value !== ''),
            'options' => [
                'providers' => AdProvider::query()->orderBy('priority')->get(['key', 'label'])->map(fn (AdProvider $provider): array => ['value' => $provider->key, 'label' => $provider->label])->all(),
                'outcomes' => array_map(fn (CallOutcome $outcome): array => ['value' => $outcome->value, 'label' => ucfirst($outcome->value)], CallOutcome::cases()),
            ],
        ]);
    }
}
