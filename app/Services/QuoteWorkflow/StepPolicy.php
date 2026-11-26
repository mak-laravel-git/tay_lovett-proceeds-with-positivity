<?php

namespace App\Services\QuoteWorkflow;

use App\Enums\AdStep;
use App\Enums\FallbackAction;
use App\Enums\FallbackReason;
use App\Models\QuoteWorkflow;
use App\Models\WorkflowEvent;

/**
 * Applies the administrator's "continue or block" rules, including the daily limit on continuing.
 */
class StepPolicy
{
    /**
     * Why the run must stay on this step, or null when it may move on.
     */
    public function refusal(QuoteWorkflow $workflow, FallbackReason $reason, ?AdStep $step = null): ?string
    {
        if (! $this->mayContinue($workflow, $reason, $step)) {
            return $reason->blockedMessage();
        }

        $limit = (int) $workflow->rule('max_fallback_skips_per_day');

        if ($limit > 0 && $this->usedToday($workflow->user_id) >= $limit) {
            return 'You have reached today\'s limit for skipping steps. '.$reason->blockedMessage();
        }

        return null;
    }

    /**
     * The rule alone, without the daily limit.
     */
    public function mayContinue(QuoteWorkflow $workflow, FallbackReason $reason, ?AdStep $step = null): bool
    {
        $setting = match ($reason) {
            FallbackReason::AdUnavailable, FallbackReason::AdError, FallbackReason::AdDismissed => ($step ?? AdStep::BeforeSurvey)->prefix().'_on_'.$reason->value,
            FallbackReason::NoSurveys, FallbackReason::SurveyApiError, FallbackReason::Disqualified => 'survey_on_'.$reason->value,
        };

        return $workflow->rule($setting) === FallbackAction::Continue->value;
    }

    public function usedToday(int $userId): int
    {
        return WorkflowEvent::query()
            ->where('user_id', $userId)
            ->where('event', WorkflowLogger::FALLBACK_EVENT)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }
}
