<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdPlacementKind;
use App\Enums\FallbackAction;
use App\Enums\PublishMode;
use App\Http\Controllers\Controller;
use App\Models\AdPlacement;
use App\Models\AdProvider;
use App\Models\GoogleAdSetting;
use App\Models\QuoteWorkflowSetting;
use App\Services\Ads\AdProviderRegistry;
use App\Services\Ads\ProviderComplianceGate;
use App\Services\Ads\Providers\GoogleAdSense\GoogleAdSenseAdapter;
use App\Services\QuoteWorkflow\WorkflowLogger;
use App\Services\QuoteWorkflow\WorkflowPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The rules of the quote workflow: which steps run, what happens when one cannot, and how quotes are published.
 */
class WorkflowSettingsController extends Controller
{
    private const ACTION_FIELDS = [
        'ad1_on_unavailable', 'ad1_on_error', 'ad1_on_dismissed',
        'survey_on_no_surveys', 'survey_on_api_error', 'survey_on_disqualified',
        'ad2_on_unavailable', 'ad2_on_error', 'ad2_on_dismissed',
    ];

    private const BOOLEAN_FIELDS = ['enabled', 'ad1_enabled', 'survey_enabled', 'ad2_enabled', 'award_survey_points'];

    public function index(AdProviderRegistry $registry, ProviderComplianceGate $compliance, WorkflowPresenter $presenter): Response
    {
        $registry->ensureRows();
        $settings = QuoteWorkflowSetting::current();

        $providers = array_values(AdProvider::query()
            ->where('key', '!=', GoogleAdSenseAdapter::KEY)
            ->orderBy('priority')
            ->get()
            ->map(function (AdProvider $provider) use ($registry, $compliance): array {
                $adapter = $registry->get($provider->key);
                $state = $compliance->launchState($provider, $adapter !== null && $adapter->isConfigured($provider));

                return [
                    'key' => $provider->key,
                    'label' => $provider->label,
                    'usable' => $state['can_launch'] && $provider->is_enabled,
                    'status' => $state['label'],
                ];
            })
            ->all());

        return Inertia::render('admin/workflow/settings', [
            'settings' => $settings->snapshot(),
            'providers' => $providers,
            'placements' => AdPlacement::query()->where('kind', AdPlacementKind::Rewarded->value)->orderBy('sort_order')->orderBy('id')->get()->map(fn (AdPlacement $placement): array => [
                'id' => $placement->id,
                'name' => $placement->name,
                'is_enabled' => $placement->is_enabled,
            ])->all(),
            'warnings' => $this->warnings($settings, $providers),
            'default_disclosure' => $presenter->disclosure([...$settings->snapshot(), 'disclosure_text' => null]),
        ]);
    }

    public function update(Request $request, AdProviderRegistry $registry, WorkflowLogger $logger): RedirectResponse
    {
        $registry->ensureRows();

        $rules = [
            'survey_provider_keys' => ['present', 'array'],
            'survey_provider_keys.*' => ['string', Rule::exists('ad_providers', 'key')->whereNot('key', GoogleAdSenseAdapter::KEY)],
            'ad1_placement_id' => ['nullable', 'integer', Rule::exists('ad_placements', 'id')->where('kind', AdPlacementKind::Rewarded->value)],
            'ad2_placement_id' => ['nullable', 'integer', Rule::exists('ad_placements', 'id')->where('kind', AdPlacementKind::Rewarded->value)],
            'publish_mode' => ['required', Rule::enum(PublishMode::class)],
            'max_fallback_skips_per_day' => ['required', 'integer', 'min:0', 'max:1000'],
            'disclosure_text' => ['nullable', 'string', 'max:1000'],
            'expires_after_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'log_retention_days' => ['required', 'integer', 'min:7', 'max:730'],
        ];

        foreach (self::BOOLEAN_FIELDS as $field) {
            $rules[$field] = ['required', 'boolean'];
        }

        foreach (self::ACTION_FIELDS as $field) {
            $rules[$field] = ['required', Rule::enum(FallbackAction::class)];
        }

        $validated = $request->validate($rules);

        foreach (self::BOOLEAN_FIELDS as $field) {
            $validated[$field] = $request->boolean($field);
        }

        $validated['survey_provider_keys'] = array_values(array_unique($validated['survey_provider_keys']));

        $this->save(QuoteWorkflowSetting::current()->fill($validated), $request, $logger, 'workflow_settings_updated');

        return back();
    }

    public function reset(Request $request, WorkflowLogger $logger): RedirectResponse
    {
        $this->save(QuoteWorkflowSetting::current()->fill(QuoteWorkflowSetting::defaults()), $request, $logger, 'workflow_settings_reset');

        return back();
    }

    private function save(QuoteWorkflowSetting $settings, Request $request, WorkflowLogger $logger, string $event): void
    {
        $changes = [];

        foreach (array_keys($settings->getDirty()) as $field) {
            $changes[$field] = ['from' => $settings->getOriginal($field), 'to' => $settings->getAttribute($field)];
        }

        $settings->save();

        if ($changes !== []) {
            $logger->log(WorkflowLogger::ADMIN, $event, context: ['changes' => $changes], userId: $request->user()->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Workflow settings saved. New runs use them.']);
    }

    /**
     * Combinations that would surprise users, spelled out for the administrator.
     *
     * @param  list<array{key: string, label: string, usable: bool, status: string}>  $providers
     * @return list<string>
     */
    private function warnings(QuoteWorkflowSetting $settings, array $providers): array
    {
        if (! $settings->enabled) {
            return ['The step-by-step flow is off: quotes are created in one press, with no ad and no survey.'];
        }

        $warnings = [];
        $google = GoogleAdSetting::current();
        $adSteps = $settings->ad1_enabled || $settings->ad2_enabled;

        if ($adSteps && ! $google->rewarded_enabled) {
            $warnings[] = 'Rewarded ads are switched off under Google ads, so both ad steps are skipped.';
        } elseif ($adSteps && $google->test_mode) {
            $warnings[] = 'Google ads test mode is on: users see Google\'s sample ad, which earns nothing.';
        } elseif ($adSteps && ! AdPlacement::query()->active(AdPlacementKind::Rewarded)->exists()) {
            $warnings[] = 'No enabled rewarded ad unit exists, so every ad step follows its "no ad available" rule.';
        }

        $usable = array_filter($providers, fn (array $provider): bool => $provider['usable']
            && in_array($provider['key'], $settings->survey_provider_keys ?? [], true));

        if ($settings->survey_enabled && $usable === []) {
            $warnings[] = 'None of the selected survey providers can currently be used, so the survey step follows its "API failure" rule.';
        }

        return $warnings;
    }
}
