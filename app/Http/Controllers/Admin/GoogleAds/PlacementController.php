<?php

namespace App\Http\Controllers\Admin\GoogleAds;

use App\Enums\AdDevice;
use App\Enums\AdLocation;
use App\Enums\AdNetwork;
use App\Enums\AdPlacementKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GoogleAds\PlacementRequest;
use App\Models\AdPlacement;
use App\Models\GoogleAdSetting;
use App\Services\QuoteWorkflow\WorkflowLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Full management of ad units: the rewarded units the workflow uses and the regular units on pages.
 */
class PlacementController extends Controller
{
    public function __construct(private readonly WorkflowLogger $logger) {}

    public function index(): Response
    {
        $settings = GoogleAdSetting::current();

        return Inertia::render('admin/google-ads/placements', [
            'placements' => AdPlacement::query()->orderBy('kind')->orderBy('sort_order')->orderBy('id')->get()->map(fn (AdPlacement $placement): array => [
                'id' => $placement->id,
                'key' => $placement->key,
                'name' => $placement->name,
                'kind' => $placement->kind->value,
                'network' => $placement->network->value,
                'location' => $placement->location?->value,
                'ad_unit_path' => $placement->ad_unit_path,
                'adsense_slot_id' => $placement->adsense_slot_id,
                'sizes' => implode(', ', $placement->sizes ?? []),
                'devices' => $placement->devices->value,
                'min_height' => $placement->min_height,
                'every_n' => $placement->every_n,
                'load_timeout_seconds' => $placement->load_timeout_seconds,
                'min_watch_seconds' => $placement->min_watch_seconds,
                'is_enabled' => $placement->is_enabled,
                'sort_order' => $placement->sort_order,
            ])->all(),
            'options' => [
                'kinds' => AdPlacementKind::options(),
                'networks' => AdNetwork::options(),
                'locations' => AdLocation::options(),
                'devices' => AdDevice::options(),
            ],
            'switches' => [
                'rewarded_enabled' => $settings->rewarded_enabled,
                'display_enabled' => $settings->display_enabled,
                'has_adsense_client' => filled($settings->adsense_client_id),
            ],
        ]);
    }

    public function store(PlacementRequest $request): RedirectResponse
    {
        $placement = AdPlacement::query()->create($request->attributesForModel());

        return $this->done($request, $placement, 'placement_created', 'Ad placement added.');
    }

    public function update(PlacementRequest $request, AdPlacement $placement): RedirectResponse
    {
        $placement->update($request->attributesForModel());

        return $this->done($request, $placement, 'placement_updated', 'Ad placement saved.');
    }

    public function destroy(Request $request, AdPlacement $placement): RedirectResponse
    {
        $placement->delete();

        return $this->done($request, $placement, 'placement_deleted', 'Ad placement removed.');
    }

    private function done(Request $request, AdPlacement $placement, string $event, string $message): RedirectResponse
    {
        $this->logger->log(WorkflowLogger::ADMIN, $event, context: [
            'placement' => $placement->key,
            'kind' => $placement->kind->value,
            'enabled' => $placement->is_enabled,
        ], userId: $request->user()?->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
