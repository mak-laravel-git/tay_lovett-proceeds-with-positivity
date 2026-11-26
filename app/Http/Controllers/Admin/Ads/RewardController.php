<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Models\AdProvider;
use App\Models\AdRewardRule;
use App\Models\AdSetting;
use App\Services\Ads\AdProviderRegistry;
use App\Support\Micros;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/**
 * Fixed points per verified completion. A provider-specific rule overrides the global one. The survey's
 * advertised reward is never used.
 */
class RewardController extends Controller
{
    public function index(AdProviderRegistry $registry): Response
    {
        $registry->ensureRows();
        $settings = AdSetting::current();

        return Inertia::render('admin/ads/rewards', [
            'rules' => AdRewardRule::query()->orderByRaw('provider_key is not null')->orderBy('provider_key')->orderBy('activity_type')->get()->map(fn (AdRewardRule $rule): array => [
                'id' => $rule->id,
                'provider_key' => $rule->provider_key,
                'activity_type' => $rule->activity_type->value,
                'points' => $rule->points,
                'is_enabled' => $rule->is_enabled,
            ])->all(),
            'providers' => AdProvider::query()->orderBy('priority')->get(['key', 'label'])->map(fn (AdProvider $provider): array => [
                'key' => $provider->key,
                'label' => $provider->label,
            ])->all(),
            'activity_types' => ActivityType::options(),
            'settings' => [
                'settlement_timezone' => $settings->settlement_timezone,
                // Shown and edited as dollars; stored as integer micros.
                'point_usd_value' => $settings->point_usd_value_micros === null ? '' : rtrim(rtrim(number_format($settings->point_usd_value_micros / Micros::PER_UNIT, 6, '.', ''), '0'), '.'),
                'reconciliation_tolerance_usd' => Micros::toDollars($settings->reconciliation_tolerance_micros),
                'reconciliation_recheck_days' => $settings->reconciliation_recheck_days,
                'offers_cache_seconds' => $settings->offers_cache_seconds,
                'user_revenue_share_enabled' => $settings->user_revenue_share_enabled,
            ],
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    /**
     * Create or replace the rule for a (provider, activity type) pair.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider_key' => ['nullable', 'string', Rule::exists('ad_providers', 'key')],
            'activity_type' => ['required', Rule::enum(ActivityType::class)],
            'points' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        AdRewardRule::query()->updateOrCreate(
            ['provider_key' => $validated['provider_key'] ?? null, 'activity_type' => $validated['activity_type']],
            ['points' => $validated['points'], 'is_enabled' => $request->boolean('is_enabled', true)],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Reward rule saved.']);

        return back();
    }

    public function update(Request $request, AdRewardRule $rule): RedirectResponse
    {
        $validated = $request->validate([
            'points' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('points', $validated)) {
            $rule->points = $validated['points'];
        }

        if (array_key_exists('is_enabled', $validated)) {
            $rule->is_enabled = $request->boolean('is_enabled');
        }

        $rule->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Reward rule updated.']);

        return back();
    }

    public function destroy(AdRewardRule $rule): RedirectResponse
    {
        $rule->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Reward rule removed.']);

        return back();
    }

    /**
     * Subsystem settings: settlement timezone, the informational value of one point, reconciliation tuning.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settlement_timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'point_usd_value' => ['nullable', 'string', 'max:20'],
            'reconciliation_tolerance_usd' => ['required', 'string', 'max:20'],
            'reconciliation_recheck_days' => ['required', 'integer', 'min:0', 'max:14'],
            'offers_cache_seconds' => ['required', 'integer', 'min:30', 'max:3600'],
            'user_revenue_share_enabled' => ['sometimes', 'boolean'],
        ]);

        try {
            $pointValue = filled($validated['point_usd_value'] ?? null) ? Micros::fromDecimal($validated['point_usd_value']) : null;
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['point_usd_value' => 'Enter a dollar amount such as 0.01.']);
        }

        try {
            $tolerance = Micros::fromDecimal($validated['reconciliation_tolerance_usd']);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['reconciliation_tolerance_usd' => 'Enter a dollar amount such as 0.01.']);
        }

        $settings = AdSetting::current();
        $settings->fill([
            'settlement_timezone' => $validated['settlement_timezone'],
            'point_usd_value_micros' => $pointValue,
            'reconciliation_tolerance_micros' => $tolerance,
            'reconciliation_recheck_days' => $validated['reconciliation_recheck_days'],
            'offers_cache_seconds' => $validated['offers_cache_seconds'],
        ]);

        if (array_key_exists('user_revenue_share_enabled', $validated)) {
            $settings->user_revenue_share_enabled = $request->boolean('user_revenue_share_enabled');
        }

        $settings->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Settings saved.']);

        return back();
    }
}
