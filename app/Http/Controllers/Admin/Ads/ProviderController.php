<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Enums\CallOutcome;
use App\Http\Controllers\Controller;
use App\Models\AdProvider;
use App\Models\AdProviderAudit;
use App\Models\AdProviderCall;
use App\Services\Ads\AdProviderRegistry;
use App\Services\Ads\Contracts\ReconcilesRevenue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin management of ad / survey providers: enable, fallback priority, credentials and connection tests.
 * Credentials are write-only: they are never sent back to the browser, only a masked hint.
 */
class ProviderController extends Controller
{
    /** Secret fields an administrator can set. Empty input keeps the stored value. */
    private const SECRET_FIELDS = ['api_token', 'api_secret', 'reporting_access_token'];

    public function index(AdProviderRegistry $registry): Response
    {
        $registry->ensureRows();

        $providers = AdProvider::query()->orderBy('priority')->orderBy('id')->get();

        return Inertia::render('admin/ads/providers', [
            'providers' => $providers->map(function (AdProvider $provider) use ($registry): array {
                $adapter = $registry->get($provider->key);

                return [
                    'id' => $provider->id,
                    'key' => $provider->key,
                    'label' => $provider->label,
                    'is_enabled' => $provider->is_enabled,
                    'priority' => $provider->priority,
                    'health' => $provider->health,
                    'registered' => $adapter !== null,
                    'configured' => $adapter !== null && $adapter->isConfigured($provider),
                    'supports_reconciliation' => $adapter instanceof ReconcilesRevenue,
                    'hints' => [
                        'api_token' => $provider->hint('api_token'),
                        'api_secret' => $provider->hint('api_secret'),
                        'reporting_access_token' => $provider->hint('reporting_access_token'),
                    ],
                    'last_tested_at' => $provider->last_tested_at?->toIso8601String(),
                    'last_success_at' => $provider->last_success_at?->toIso8601String(),
                    'last_error_category' => $provider->last_error_category,
                    'callback_url' => url("/api/webhooks/{$provider->key}/reward"),
                    'return_url' => url("/surveys/return/{$provider->key}"),
                ];
            })->all(),
            // The order the fallback chain will actually use right now.
            'fallback_order' => $providers
                ->filter(fn (AdProvider $provider): bool => $provider->is_enabled)
                ->map(fn (AdProvider $provider): array => [
                    'key' => $provider->key,
                    'label' => $provider->label,
                    'configured' => ($registry->get($provider->key)?->isConfigured($provider)) === true,
                ])->values()->all(),
            'audits' => AdProviderAudit::query()->with(['user:id,name', 'provider:id,label'])->latest()->limit(15)->get()->map(fn (AdProviderAudit $audit): array => [
                'id' => $audit->id,
                'action' => $audit->action,
                'provider' => $audit->provider?->label,
                'user' => $audit->user?->name,
                'at' => $audit->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function update(Request $request, AdProvider $provider, AdProviderRegistry $registry): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:9999'],
            'api_token' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'reporting_access_token' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = $provider->settings ?? [];
        $changed = [];

        foreach (self::SECRET_FIELDS as $field) {
            if (filled($validated[$field] ?? null)) {
                $settings[$field] = $validated[$field];
                $changed[] = $field;
            }
        }

        $provider->settings = $settings;

        if (array_key_exists('priority', $validated) && $validated['priority'] !== $provider->priority) {
            $provider->priority = $validated['priority'];
            $changed[] = 'priority';
        }

        if (array_key_exists('is_enabled', $validated) && $request->boolean('is_enabled') !== $provider->is_enabled) {
            $enable = $request->boolean('is_enabled');
            $adapter = $registry->get($provider->key);

            // Enabling is judged on the credentials as they will be after this request is saved.
            if ($enable && ($adapter === null || ! $adapter->isConfigured($provider))) {
                throw ValidationException::withMessages([
                    'is_enabled' => 'Add the API token and signing secret before enabling this provider.',
                ]);
            }

            $provider->is_enabled = $enable;
            $changed[] = $enable ? 'enabled' : 'disabled';
        }

        if ($changed === []) {
            return back();
        }

        $provider->save();
        $this->audit($request, $provider, 'updated', ['changed' => $changed]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $provider->label.' updated.']);

        return back();
    }

    /**
     * Move a provider one place up or down the fallback order. Priorities are renumbered so they stay distinct.
     */
    public function move(Request $request, AdProvider $provider): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        DB::transaction(function () use ($provider, $direction, $request): void {
            $ordered = AdProvider::query()->orderBy('priority')->orderBy('id')->lockForUpdate()->get()->values();
            $index = $ordered->search(fn (AdProvider $row): bool => $row->id === $provider->id);

            if ($index === false) {
                return;
            }

            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if (! isset($ordered[$target])) {
                return;
            }

            $list = $ordered->all();
            [$list[$index], $list[$target]] = [$list[$target], $list[$index]];

            foreach ($list as $position => $row) {
                $row->update(['priority' => ($position + 1) * 10]);
            }

            $this->audit($request, $provider, 'reordered', ['direction' => $direction]);
        });

        return back();
    }

    public function test(Request $request, AdProvider $provider, AdProviderRegistry $registry): RedirectResponse
    {
        $adapter = $registry->get($provider->key);

        if ($adapter === null || ! $adapter->isConfigured($provider)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $provider->label.': add the API token and signing secret first.']);

            return back();
        }

        $result = $adapter->testConnection($provider);
        $ok = $result->outcome === CallOutcome::Success;

        AdProviderCall::query()->create([
            'provider_key' => $provider->key,
            'operation' => 'test_connection',
            'outcome' => $result->outcome,
            'http_status' => $result->httpStatus,
            'latency_ms' => $result->latencyMs,
            'error_category' => $result->errorCategory,
        ]);

        $provider->update([
            'health' => $ok ? 'healthy' : 'unhealthy',
            'last_tested_at' => now(),
            'last_error_category' => $ok ? null : $result->errorCategory,
            'last_success_at' => $ok ? now() : $provider->last_success_at,
        ]);

        $this->audit($request, $provider, 'tested', ['ok' => $ok, 'error' => $result->errorCategory]);

        Inertia::flash('toast', [
            'type' => $ok ? 'success' : 'error',
            'message' => $provider->label.($result->latencyMs !== null ? " ({$result->latencyMs} ms)" : '').': '
                .($ok ? 'connection works.' : 'failed ('.($result->errorCategory ?? 'unknown error').').'),
        ]);

        return back();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function audit(Request $request, AdProvider $provider, string $action, array $meta = []): void
    {
        AdProviderAudit::query()->create([
            'user_id' => $request->user()?->id,
            'ad_provider_id' => $provider->id,
            'action' => $action,
            'meta' => $meta,
        ]);
    }
}
