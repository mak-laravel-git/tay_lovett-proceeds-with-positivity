<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Single-row settings for Google ads (same pattern as AdSetting).
 *
 * @property bool $rewarded_enabled
 * @property bool $display_enabled
 * @property bool $test_mode
 * @property string|null $adsense_client_id
 * @property string|null $gam_network_code
 * @property string|null $ads_txt
 */
#[Fillable(['rewarded_enabled', 'display_enabled', 'adsense_client_id', 'gam_network_code', 'test_mode', 'ads_txt'])]
class GoogleAdSetting extends Model
{
    /** Google's own sample units, used while test mode is on. */
    public const TEST_REWARDED_UNIT = '/22639388115/rewarded_web_example';

    public const TEST_DISPLAY_UNIT = '/6355419/Travel';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rewarded_enabled' => 'boolean',
            'display_enabled' => 'boolean',
            'test_mode' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $settings = self::query()->firstOrCreate([]);

        return $settings->wasRecentlyCreated ? $settings->refresh() : $settings;
    }
}
