<?php

namespace App\Models;

use App\Enums\FallbackAction;
use App\Enums\PublishMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Single-row rules for the quote workflow. Everything here is edited on the admin "Quote workflow" page.
 *
 * @property bool $enabled
 * @property bool $ad1_enabled
 * @property string $ad1_on_unavailable
 * @property string $ad1_on_error
 * @property string $ad1_on_dismissed
 * @property int|null $ad1_placement_id
 * @property bool $survey_enabled
 * @property string $survey_on_no_surveys
 * @property string $survey_on_api_error
 * @property string $survey_on_disqualified
 * @property list<string>|null $survey_provider_keys
 * @property bool $award_survey_points
 * @property bool $ad2_enabled
 * @property string $ad2_on_unavailable
 * @property string $ad2_on_error
 * @property string $ad2_on_dismissed
 * @property int|null $ad2_placement_id
 * @property string $publish_mode
 * @property int $max_fallback_skips_per_day
 * @property string|null $disclosure_text
 * @property int $expires_after_hours
 * @property int $log_retention_days
 */
#[Fillable([
    'enabled',
    'ad1_enabled', 'ad1_on_unavailable', 'ad1_on_error', 'ad1_on_dismissed', 'ad1_placement_id',
    'survey_enabled', 'survey_on_no_surveys', 'survey_on_api_error', 'survey_on_disqualified', 'survey_provider_keys', 'award_survey_points',
    'ad2_enabled', 'ad2_on_unavailable', 'ad2_on_error', 'ad2_on_dismissed', 'ad2_placement_id',
    'publish_mode', 'max_fallback_skips_per_day', 'disclosure_text', 'expires_after_hours', 'log_retention_days',
])]
class QuoteWorkflowSetting extends Model
{
    /** Providers that report each completion against the exact attempt, so they suit the workflow by default. */
    public const DEFAULT_PROVIDERS = ['tapresearch', 'theoremreach'];

    /**
     * The factory values, identical to the migration defaults.
     *
     * @return array<string, bool|int|string|list<string>|null>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'ad1_enabled' => true,
            'ad1_on_unavailable' => FallbackAction::Continue->value,
            'ad1_on_error' => FallbackAction::Block->value,
            'ad1_on_dismissed' => FallbackAction::Block->value,
            'ad1_placement_id' => null,
            'survey_enabled' => true,
            'survey_on_no_surveys' => FallbackAction::Block->value,
            'survey_on_api_error' => FallbackAction::Block->value,
            'survey_on_disqualified' => FallbackAction::Block->value,
            'survey_provider_keys' => self::DEFAULT_PROVIDERS,
            'award_survey_points' => false,
            'ad2_enabled' => true,
            'ad2_on_unavailable' => FallbackAction::Block->value,
            'ad2_on_error' => FallbackAction::Block->value,
            'ad2_on_dismissed' => FallbackAction::Block->value,
            'ad2_placement_id' => null,
            'publish_mode' => PublishMode::Auto->value,
            'max_fallback_skips_per_day' => 3,
            'disclosure_text' => null,
            'expires_after_hours' => 24,
            'log_retention_days' => 90,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'ad1_enabled' => 'boolean',
            'ad1_placement_id' => 'integer',
            'survey_enabled' => 'boolean',
            'survey_provider_keys' => 'array',
            'award_survey_points' => 'boolean',
            'ad2_enabled' => 'boolean',
            'ad2_placement_id' => 'integer',
            'max_fallback_skips_per_day' => 'integer',
            'expires_after_hours' => 'integer',
            'log_retention_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        $settings = self::query()->first()
            ?? self::query()->create(['survey_provider_keys' => self::DEFAULT_PROVIDERS]);

        return $settings->wasRecentlyCreated ? $settings->refresh() : $settings;
    }

    /**
     * The rules as plain values, stored on each run when it starts.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $snapshot = [];

        foreach (array_keys(self::defaults()) as $field) {
            $snapshot[$field] = $this->getAttribute($field);
        }

        $snapshot['survey_provider_keys'] = $this->survey_provider_keys ?? self::DEFAULT_PROVIDERS;

        return $snapshot;
    }
}
