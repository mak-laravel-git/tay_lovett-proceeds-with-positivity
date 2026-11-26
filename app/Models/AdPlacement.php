<?php

namespace App\Models;

use App\Enums\AdDevice;
use App\Enums\AdLocation;
use App\Enums\AdNetwork;
use App\Enums\AdPlacementKind;
use Database\Factories\AdPlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One configured Google ad unit.
 *
 * @use HasFactory<AdPlacementFactory>
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property AdPlacementKind $kind
 * @property AdNetwork $network
 * @property AdLocation|null $location
 * @property AdDevice $devices
 * @property string|null $ad_unit_path
 * @property string|null $adsense_slot_id
 * @property list<string>|null $sizes
 * @property bool $is_enabled
 * @property int $min_height
 * @property int|null $every_n
 * @property int $load_timeout_seconds
 * @property int $min_watch_seconds
 * @property int $sort_order
 */
#[Fillable([
    'key', 'name', 'kind', 'network', 'location', 'ad_unit_path', 'adsense_slot_id', 'sizes', 'devices',
    'min_height', 'every_n', 'load_timeout_seconds', 'min_watch_seconds', 'is_enabled', 'sort_order',
])]
class AdPlacement extends Model
{
    /** @use HasFactory<AdPlacementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AdPlacementKind::class,
            'network' => AdNetwork::class,
            'location' => AdLocation::class,
            'devices' => AdDevice::class,
            'sizes' => 'array',
            'is_enabled' => 'boolean',
            'min_height' => 'integer',
            'every_n' => 'integer',
            'load_timeout_seconds' => 'integer',
            'min_watch_seconds' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Enabled placements of one kind, in the order they should be preferred.
     *
     * @param  Builder<AdPlacement>  $query
     * @return Builder<AdPlacement>
     */
    public function scopeActive(Builder $query, AdPlacementKind $kind): Builder
    {
        return $query->where('kind', $kind->value)->where('is_enabled', true)->orderBy('sort_order')->orderBy('id');
    }
}
