<?php

namespace App\Enums;

/**
 * The two places in the quote workflow where a rewarded ad runs.
 */
enum AdStep: string
{
    case BeforeSurvey = 'before_survey';
    case BeforePublish = 'before_publish';

    public function label(): string
    {
        return match ($this) {
            self::BeforeSurvey => 'Ad before survey',
            self::BeforePublish => 'Ad before publishing',
        };
    }

    public function state(): WorkflowState
    {
        return match ($this) {
            self::BeforeSurvey => WorkflowState::AdBeforeSurvey,
            self::BeforePublish => WorkflowState::AdBeforePublish,
        };
    }

    /**
     * The prefix of this step's settings (`ad1_enabled`, `ad2_on_error`, ...).
     */
    public function prefix(): string
    {
        return match ($this) {
            self::BeforeSurvey => 'ad1',
            self::BeforePublish => 'ad2',
        };
    }

    public static function forState(WorkflowState $state): ?self
    {
        return match ($state) {
            WorkflowState::AdBeforeSurvey => self::BeforeSurvey,
            WorkflowState::AdBeforePublish => self::BeforePublish,
            default => null,
        };
    }
}
