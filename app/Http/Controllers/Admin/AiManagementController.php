<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AiDriver;
use App\Http\Controllers\Controller;
use App\Models\AiCredentialAudit;
use App\Models\AiGenerationLog;
use App\Models\AiModel;
use App\Models\AiProviderCredential;
use App\Models\AiRouteStep;
use App\Models\AiSetting;
use App\Services\QuoteRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AiManagementController extends Controller
{
    public function providers(): Response
    {
        $enabledModels = AiModel::query()->where('is_enabled', true)->pluck('driver')->countBy();

        return Inertia::render('admin/ai/providers', [
            'credentials' => AiProviderCredential::query()->orderBy('priority')->orderBy('id')->get()->map(fn (AiProviderCredential $credential): array => [
                'id' => $credential->id,
                'driver' => $credential->driver,
                'driver_label' => AiDriver::labelFor($credential->driver),
                'label' => $credential->label,
                'priority' => $credential->priority,
                'is_enabled' => $credential->is_enabled,
                'health' => $credential->health,
                'key_hint' => $this->keyHint($credential),
                'url' => $credential->payload['url'] ?? null,
                'enabled_models' => (int) ($enabledModels[$credential->driver] ?? 0),
                'last_tested_at' => $credential->last_tested_at?->toIso8601String(),
                'last_error_category' => $credential->last_error_category,
            ]),
            'drivers' => AiDriver::options(),
        ]);
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver' => ['required', Rule::in(AiDriver::values())],
            'label' => ['required', 'string', 'max:80'],
            'key' => ['required_unless:driver,ollama', 'nullable', 'string', 'max:500'],
            'url' => ['required_if:driver,ollama', 'nullable', 'url', 'max:255'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $credential = AiProviderCredential::query()->create([
            'driver' => $validated['driver'],
            'label' => $validated['label'],
            'priority' => $validated['priority'],
            'is_enabled' => true,
            'payload' => array_filter([
                'key' => $validated['key'] ?? null,
                'url' => $validated['url'] ?? null,
            ], filled(...)),
        ]);

        $this->audit($request, $credential, 'created');
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Provider added. Run a connection test to confirm it works.']);

        return back();
    }

    public function updateProvider(Request $request, AiProviderCredential $credential): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'key' => ['nullable', 'string', 'max:500'],
            'url' => [Rule::requiredIf($credential->driver === AiDriver::Ollama->value), 'nullable', 'url', 'max:255'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $payload = $credential->payload;

        if (filled($validated['key'] ?? null)) {
            $payload['key'] = $validated['key'];
        }

        $payload['url'] = $validated['url'] ?? null;
        $keyChanged = filled($validated['key'] ?? null);

        $credential->update([
            'label' => $validated['label'],
            'priority' => $validated['priority'],
            'is_enabled' => $validated['is_enabled'],
            'payload' => array_filter($payload, filled(...)),
            ...($keyChanged ? ['health' => 'unknown', 'last_error_category' => null] : []),
        ]);

        $this->audit($request, $credential, $keyChanged ? 'key_rotated' : 'updated');
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Provider updated.']);

        return back();
    }

    public function destroyProvider(Request $request, AiProviderCredential $credential): RedirectResponse
    {
        AiRouteStep::query()->where('ai_provider_credential_id', $credential->id)->delete();
        $credential->delete();

        $this->audit($request, $credential, 'deleted');
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Provider deleted.']);

        return back();
    }

    public function testConnection(Request $request, AiProviderCredential $credential, QuoteRouter $router): RedirectResponse
    {
        $result = $router->testConnection($request->user(), $credential);
        $model = $result['model'] ? ' ('.$result['model'].', '.$result['latency_ms'].' ms)' : '';

        Inertia::flash('toast', [
            'type' => $result['ok'] ? 'success' : 'error',
            'message' => $credential->label.$model.': '.$result['message'],
        ]);

        return back();
    }

    /**
     * Test a specific model directly, using the first enabled provider credential of the same driver.
     */
    public function testModel(Request $request, AiModel $model, QuoteRouter $router): RedirectResponse
    {
        $credential = AiProviderCredential::query()
            ->where('driver', $model->driver)
            ->where('is_enabled', true)
            ->orderBy('priority')
            ->first();

        if ($credential === null) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $model->name.': No enabled '.AiDriver::labelFor($model->driver).' provider to test it with. Add one in Providers first.',
            ]);

            return back();
        }

        $result = $router->testConnection($request->user(), $credential, $model);
        $latency = $result['latency_ms'] !== null ? ' ('.$result['latency_ms'].' ms)' : '';

        Inertia::flash('toast', [
            'type' => $result['ok'] ? 'success' : 'error',
            'message' => $model->name.' via '.$credential->label.$latency.': '.$result['message'],
        ]);

        return back();
    }

    public function models(): Response
    {
        $enabledProviderDrivers = AiProviderCredential::query()
            ->where('is_enabled', true)
            ->pluck('driver')
            ->unique()
            ->values();

        return Inertia::render('admin/ai/models', [
            'models' => AiModel::query()->orderBy('driver')->orderBy('name')->get()->map(fn (AiModel $model): array => [
                'id' => $model->id,
                'driver' => $model->driver,
                'driver_label' => AiDriver::labelFor($model->driver),
                'name' => $model->name,
                'max_request_cents' => $model->max_request_cents,
                'is_enabled' => $model->is_enabled,
                'structured_output_tested' => $model->structured_output_tested,
                'has_enabled_provider' => $enabledProviderDrivers->contains($model->driver),
            ]),
            'drivers' => AiDriver::options(),
            'providerDrivers' => $enabledProviderDrivers,
        ]);
    }

    public function storeModel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver' => ['required', Rule::in(AiDriver::values())],
            'name' => ['required', 'string', 'max:120', Rule::unique('ai_models', 'name')->where('driver', $request->input('driver'))],
            'max_request_cents' => ['required', 'integer', 'min:1'],
        ], [
            'name.unique' => 'This model is already added for that provider.',
        ]);

        AiModel::query()->create([
            ...$validated,
            'is_enabled' => true,
            'structured_output_tested' => false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Model added.']);

        return back();
    }

    public function updateModel(Request $request, AiModel $model): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('ai_models', 'name')->where('driver', $model->driver)->ignore($model->id)],
            'max_request_cents' => ['required', 'integer', 'min:1'],
            'is_enabled' => ['required', 'boolean'],
        ], [
            'name.unique' => 'This model is already added for that provider.',
        ]);

        $model->update([
            ...$validated,
            ...($validated['name'] !== $model->name ? ['structured_output_tested' => false] : []),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Model updated.']);

        return back();
    }

    public function destroyModel(AiModel $model): RedirectResponse
    {
        $model->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Model deleted.']);

        return back();
    }

    public function routing(QuoteRouter $router): Response
    {
        return Inertia::render('admin/ai/routing', [
            'mode' => AiSetting::current()->routing_mode,
            'steps' => AiRouteStep::query()
                ->with(['credential', 'model'])
                ->orderBy('position')
                ->get()
                ->map(fn (AiRouteStep $step): array => [
                    'id' => $step->id,
                    'credential_label' => $step->credential?->label ?? 'Deleted provider',
                    'driver_label' => AiDriver::labelFor($step->credential?->driver ?? ''),
                    'model_name' => $step->model?->name ?? 'Deleted model',
                    'usable' => $step->credential !== null
                        && ! $step->credential->trashed()
                        && $step->credential->is_enabled
                        && $step->model?->is_enabled === true,
                ]),
            'credentials' => AiProviderCredential::query()->where('is_enabled', true)->orderBy('priority')->get(['id', 'driver', 'label'])->map(fn (AiProviderCredential $credential): array => [
                'id' => $credential->id,
                'label' => $credential->label,
                'driver' => $credential->driver,
                'driver_label' => AiDriver::labelFor($credential->driver),
            ]),
            'models' => AiModel::query()->where('is_enabled', true)->orderBy('id')->get(['id', 'driver', 'name']),
            'simulation' => $router->simulate(),
            'readiness' => $router->readiness(),
        ]);
    }

    public function updateRoutingMode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'routing_mode' => ['required', Rule::in(['priority', 'manual'])],
        ]);

        AiSetting::current()->update($validated);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Routing mode updated.']);

        return back();
    }

    public function storeRouteStep(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_provider_credential_id' => ['required', Rule::exists('ai_provider_credentials', 'id')->where('is_enabled', true)->whereNull('deleted_at')],
            'ai_model_id' => ['required', Rule::exists('ai_models', 'id')->where('is_enabled', true)],
        ]);

        $credential = AiProviderCredential::query()->findOrFail($validated['ai_provider_credential_id']);
        $model = AiModel::query()->findOrFail($validated['ai_model_id']);

        if ($credential->driver !== $model->driver) {
            return back()->withErrors(['ai_model_id' => 'Pick a model that belongs to the same provider type.']);
        }

        AiRouteStep::query()->create([
            ...$validated,
            'position' => ((int) AiRouteStep::query()->max('position')) + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Step added to the route.']);

        return back();
    }

    public function moveRouteStep(Request $request, AiRouteStep $step): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        $ordered = AiRouteStep::query()->orderBy('position')->orderBy('id')->get()->values();
        $index = $ordered->search(fn (AiRouteStep $item): bool => $item->id === $step->id);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $target < 0 || $target >= $ordered->count()) {
            return back();
        }

        $swapped = $ordered->all();
        [$swapped[$index], $swapped[$target]] = [$swapped[$target], $swapped[$index]];

        foreach ($swapped as $position => $item) {
            if ($item->position !== $position + 1) {
                $item->update(['position' => $position + 1]);
            }
        }

        return back();
    }

    public function destroyRouteStep(AiRouteStep $step): RedirectResponse
    {
        $step->delete();

        foreach (AiRouteStep::query()->orderBy('position')->orderBy('id')->get() as $index => $item) {
            $item->update(['position' => $index + 1]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Step removed.']);

        return back();
    }

    public function usage(): Response
    {
        $logs = AiGenerationLog::query()
            ->with(['user:id,name,role'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (AiGenerationLog $log): array => [
                'id' => $log->id,
                'driver' => $log->driver,
                'model' => $log->model,
                'credential_label' => $log->credential_label,
                'input_tokens' => $log->input_tokens,
                'output_tokens' => $log->output_tokens,
                'reserved_cents' => $log->reserved_cents,
                'spent_cents' => $log->spent_cents,
                'error_category' => $log->error_category,
                'created_at' => $log->created_at?->toIso8601String(),
                'user' => $log->user === null ? null : [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'role' => $log->user->role->value,
                ],
            ]);

        return Inertia::render('admin/ai/usage', [
            'logs' => $logs,
        ]);
    }

    public function settings(QuoteRouter $router): Response
    {
        return Inertia::render('admin/ai/settings', [
            'settings' => AiSetting::current(),
            'readiness' => $router->readiness(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'routing_mode' => ['required', Rule::in(['priority', 'manual'])],
            'timeout_seconds' => ['required', 'integer', 'min:5', 'max:60'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:5'],
            'daily_budget_cents' => ['required', 'integer', 'min:0'],
            'generation_enabled' => ['required', 'boolean'],
            'default_cooldown_seconds' => ['required', 'integer', 'min:1', 'max:3600'],
        ]);

        AiSetting::current()->update($validated);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'AI settings saved.']);

        return back();
    }

    public function resetSettings(): RedirectResponse
    {
        AiSetting::current()->update(AiSetting::defaults());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'AI settings were reset to their defaults.']);

        return back();
    }

    private function keyHint(AiProviderCredential $credential): ?string
    {
        $key = (string) ($credential->payload['key'] ?? '');

        return $key === '' ? null : '••••'.substr($key, -4);
    }

    private function audit(Request $request, AiProviderCredential $credential, string $action): void
    {
        AiCredentialAudit::query()->create([
            'user_id' => $request->user()->id,
            'ai_provider_credential_id' => $credential->id,
            'action' => $action,
        ]);
    }
}
