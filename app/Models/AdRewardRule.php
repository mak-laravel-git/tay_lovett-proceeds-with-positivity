<?php

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Fixed points awarded for one verified completion. `provider_key = null` is the global rule; a rule with a
 * provider key overrides it for that provider.
 *
 * @property ActivityType $activity_type
 */
#[Fillable(['provider_key', 'activity_type', 'points', 'is_enabled'])]
class AdRewardRule extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_type' => ActivityType::class,
            'points' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }
}
