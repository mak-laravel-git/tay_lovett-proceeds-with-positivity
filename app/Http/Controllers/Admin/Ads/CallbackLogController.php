<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Enums\CallbackResult;
use App\Http\Controllers\Controller;
use App\Models\ProviderCallback;
use App\Support\Micros;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every inbound provider callback, valid or not, for audit.
 */
class CallbackLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'result' => ['nullable', 'string', 'in:'.implode(',', array_column(CallbackResult::cases(), 'value'))],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $callbacks = ProviderCallback::query()
            ->when($filters['result'] ?? null, fn (Builder $builder, string $result) => $builder->where('result', $result))
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $builder->where(fn (Builder $inner) => $inner->where('provider_transaction_id', 'like', $like)->orWhere('activity_id', 'like', $like));
            })
            ->latest('received_at')->latest('id')->paginate(30)->withQueryString()
            ->through(fn (ProviderCallback $callback): array => [
                'id' => $callback->id,
                'received_at' => $callback->received_at->toIso8601String(),
                'provider' => $callback->provider_key,
                'transaction_id' => $callback->provider_transaction_id,
                'activity_id' => $callback->activity_id,
                'result' => $callback->result->value,
                'result_label' => $callback->result->label(),
                'signature_valid' => $callback->signature_valid,
                'note' => $callback->note,
                'revenue_usd' => Micros::toDollars($callback->revenue_micros),
                'ip' => $callback->ip,
                'payload' => $callback->payload,
            ]);

        return Inertia::render('admin/ads/callbacks', [
            'callbacks' => $callbacks,
            'filters' => array_filter($filters, fn ($value) => $value !== null && $value !== ''),
            'results' => CallbackResult::options(),
        ]);
    }
}
