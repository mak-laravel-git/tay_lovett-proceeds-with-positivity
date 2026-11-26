<?php

namespace Database\Factories;

use App\Enums\AdDevice;
use App\Enums\AdLocation;
use App\Enums\AdNetwork;
use App\Enums\AdPlacementKind;
use App\Models\AdPlacement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdPlacement>
 */
class AdPlacementFactory extends Factory
{
    /**
     * A rewarded Ad Manager unit.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'unit-'.Str::lower(Str::random(10)),
            'name' => 'Rewarded unit',
            'kind' => AdPlacementKind::Rewarded,
            'network' => AdNetwork::AdManager,
            'location' => null,
            'ad_unit_path' => '/1234567/rewarded',
            'devices' => AdDevice::All,
            'load_timeout_seconds' => 10,
            'min_watch_seconds' => 5,
            'is_enabled' => true,
            'sort_order' => 10,
        ];
    }

    public function display(AdLocation $location = AdLocation::FeedTop): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Display unit',
            'kind' => AdPlacementKind::Display,
            'network' => AdNetwork::AdSense,
            'location' => $location,
            'ad_unit_path' => null,
            'adsense_slot_id' => '1234567890',
        ]);
    }
}
