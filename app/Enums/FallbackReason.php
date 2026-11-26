<?php

namespace App\Enums;

/**
 * Why a step could not be completed. Each reason has its own continue / block setting.
 */
enum FallbackReason: string
{
    case AdUnavailable = 'unavailable';
    case AdError = 'error';
    case AdDismissed = 'dismissed';
    case NoSurveys = 'no_surveys';
    case SurveyApiError = 'api_error';
    case Disqualified = 'disqualified';

    /**
     * The `*_result` value stored on a run that moved on for this reason.
     */
    public function skippedResult(): string
    {
        return 'skipped_'.$this->value;
    }

    /**
     * What the user is told when the rules keep them on the step.
     */
    public function blockedMessage(): string
    {
        return match ($this) {
            self::AdUnavailable => 'No ad is available right now. Please try again in a few minutes.',
            self::AdError => 'The ad could not be loaded. Turn off any ad blocker for this site and try again.',
            self::AdDismissed => 'The ad was closed before it finished. Watch the whole ad to continue.',
            self::NoSurveys => 'No surveys are available right now. Please try again in a few minutes.',
            self::SurveyApiError => 'Surveys could not be loaded. Please try again in a few minutes.',
            self::Disqualified => 'That survey was not completed. Choose another one to continue.',
        };
    }
}
