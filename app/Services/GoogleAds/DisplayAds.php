<?php

namespace App\Services\GoogleAds;

use App\Enums\AdNetwork;
use App\Enums\AdPlacementKind;
use App\Models\AdPlacement;
use App\Models\GoogleAdSetting;
use Illuminate\Http\Request;

/**
 * The regular (display) ads a page may show. These are independent of the quote workflow, and never appear on
 * administration or sign-in screens.
 */
class DisplayAds
{
    /** Request paths that never carry ads. */
    private const EXCLUDED_PATHS = [
        'admin', 'admin/*', 'login', 'register', 'forgot-password', 'reset-password*', 'verify*', 'email/*',
        'two-factor-challenge', 'confirm-access/*', 'user/*', 'settings', 'settings/*', 'surveys/return/*',
    ];

    /**
     * @return array{client: string|null, test_mode: bool, placements: list<array<string, mixed>>}|null
     */
    public function forRequest(Request $request): ?array
    {
        if ($request->is(...self::EXCLUDED_PATHS)) {
            return null;
        }

        // Read without creating the row: this runs on every page.
        $settings = GoogleAdSetting::query()->first();

        if ($settings === null || ! $settings->display_enabled) {
            return null;
        }

        $placements = array_values(AdPlacement::query()
            ->active(AdPlacementKind::Display)
            ->get()
            ->map(fn (AdPlacement $placement): ?array => $this->present($placement, $settings))
            ->filter()
            ->all());

        return $placements === [] ? null : [
            'client' => $settings->adsense_client_id,
            'test_mode' => $settings->test_mode,
            'placements' => $placements,
        ];
    }

    /**
     * Null when the placement is not complete enough to request an ad.
     *
     * @return array<string, mixed>|null
     */
    private function present(AdPlacement $placement, GoogleAdSetting $settings): ?array
    {
        if ($placement->location === null) {
            return null;
        }

        $path = null;
        $slot = null;

        if ($placement->network === AdNetwork::AdSense) {
            $slot = $placement->adsense_slot_id;

            if (blank($slot) || blank($settings->adsense_client_id)) {
                return null;
            }
        } else {
            $path = $settings->test_mode ? GoogleAdSetting::TEST_DISPLAY_UNIT : $placement->ad_unit_path;

            if (blank($path)) {
                return null;
            }
        }

        return [
            'id' => $placement->id,
            'location' => $placement->location->value,
            'network' => $placement->network->value,
            'ad_unit_path' => $path,
            'adsense_slot_id' => $slot,
            'sizes' => $this->sizes($placement->sizes),
            'devices' => $placement->devices->value,
            'min_height' => $placement->min_height,
            'every_n' => $placement->every_n,
        ];
    }

    /**
     * @param  list<string>|null  $sizes  Values such as "300x250".
     * @return list<array{0: int, 1: int}>
     */
    private function sizes(?array $sizes): array
    {
        $parsed = [];

        foreach ($sizes ?? [] as $size) {
            if (preg_match('/^(\d{1,4})x(\d{1,4})$/', $size, $parts) === 1) {
                $parsed[] = [(int) $parts[1], (int) $parts[2]];
            }
        }

        return $parsed;
    }
}
