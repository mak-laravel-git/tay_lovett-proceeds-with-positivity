<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Single-row settings for the ads/survey subsystem (same pattern as AiSetting).
 */
#[Fillable(['settlement_timezone', 'point_usd_value_micros', 'reconciliation_tolerance_micros', 'reconciliation_recheck_days', 'offers_cache_seconds', 'gate_quote_generation', 'user_revenue_share_enabled'])]
class AdSetting extends Model
{
    /**
     * The factory values, identical to the migration defaults.
     *
     * @return array{settlement_timezone: string, point_usd_value_micros: int|null, reconciliation_tolerance_micros: int, reconciliation_recheck_days: int, offers_cache_seconds: int, gate_quote_generation: bool, user_revenue_share_enabled: bool}
     */
    public static function defaults(): array
    {
        return [
            'settlement_timezone' => 'America/New_York',
            'point_usd_value_micros' => null,
            'reconciliation_tolerance_micros' => 10000,
            'reconciliation_recheck_days' => 3,
            'offers_cache_seconds' => 120,
            'gate_quote_generation' => true,
            'user_revenue_share_enabled' => false,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gate_quote_generation' => 'boolean',
            'user_revenue_share_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $settings = self::query()->firstOrCreate([]);

        return $settings->wasRecentlyCreated ? $settings->refresh() : $settings;
    }

    /**
     * The settlement timezone without creating a row, safe to call while the schema may not exist yet
     * (scheduler boot, fresh installs).
     */
    public static function timezoneOrDefault(): string
    {
        $timezone = rescue(fn () => self::query()->value('settlement_timezone'), null, false);

        return is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : self::defaults()['settlement_timezone'];
    }
}
