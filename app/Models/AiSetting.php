<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['routing_mode', 'timeout_seconds', 'max_attempts', 'daily_budget_cents', 'generation_enabled', 'default_cooldown_seconds'])]
class AiSetting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generation_enabled' => 'boolean',
        ];
    }

    /**
     * The factory values, identical to the migration defaults.
     *
     * @return array{routing_mode: string, timeout_seconds: int, max_attempts: int, daily_budget_cents: int, generation_enabled: bool, default_cooldown_seconds: int}
     */
    public static function defaults(): array
    {
        return [
            'routing_mode' => 'priority',
            'timeout_seconds' => 20,
            'max_attempts' => 3,
            'daily_budget_cents' => 0,
            'generation_enabled' => false,
            'default_cooldown_seconds' => 60,
        ];
    }

    public static function current(): self
    {
        $settings = self::query()->firstOrCreate([]);

        return $settings->wasRecentlyCreated ? $settings->refresh() : $settings;
    }
}
