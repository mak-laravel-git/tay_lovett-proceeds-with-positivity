<?php

namespace App\Services\QuoteWorkflow;

use App\Enums\ActivityStatus;
use App\Enums\AdStep;
use App\Enums\FallbackReason;
use App\Enums\WorkflowState;
use App\Models\QuoteWorkflow;

/**
 * What the browser is allowed to know about a run. The quote's text is only included once the run is complete.
 */
class WorkflowPresenter
{
    public function __construct(private readonly RewardedAdService $ads) {}

    /**
     * @return array<string, mixed>
     */
    public function present(QuoteWorkflow $workflow): array
    {
        $workflow->loadMissing('category');
        $state = $workflow->state;
        $latest = $workflow->surveys()->orderByDesc('id')->first();
        $quote = $state === WorkflowState::Completed ? $workflow->quote()->first() : null;

        $notice = $state === WorkflowState::Survey && $latest?->status === ActivityStatus::Rejected
            ? FallbackReason::Disqualified->blockedMessage()
            : null;

        return [
            'id' => $workflow->id,
            'state' => $state->value,
            'active' => $state->isActive(),
            'category' => [
                'id' => $workflow->quote_category_id,
                'name' => $workflow->category?->name,
            ],
            'steps' => $this->steps($workflow),
            'survey' => [
                'awards_points' => (bool) $workflow->rule('award_survey_points'),
                'pending' => $latest !== null && $latest->status->isPending(),
            ],
            'quote' => $quote === null ? null : [
                'slug' => $quote->slug,
                'text' => $quote->text,
                'status' => $quote->status->value,
            ],
            'notice' => $notice,
            'can_cancel' => $state->isBeforeGeneration(),
        ];
    }

    /**
     * What a user is told before starting: every step they may be asked to do. The administrator can replace it.
     *
     * @param  array<string, mixed>  $rules
     */
    public function disclosure(array $rules): string
    {
        $custom = trim((string) ($rules['disclosure_text'] ?? ''));

        if ($custom !== '') {
            return $custom;
        }

        $steps = [];

        if ($this->ads->stepActive($rules, AdStep::BeforeSurvey)) {
            $steps[] = 'watch a short ad';
        }

        if ($rules['survey_enabled'] ?? false) {
            $steps[] = 'complete one survey';
        }

        if ($this->ads->stepActive($rules, AdStep::BeforePublish)) {
            $steps[] = $steps === [] ? 'watch a short ad' : 'watch a second short ad';
        }

        if ($steps === []) {
            return 'Press the button to create a quote.';
        }

        $last = array_pop($steps);
        $list = $steps === [] ? $last : implode(', ', $steps).' and '.$last;
        $text = 'To create a quote you will be asked to '.$list.'.';

        if ($this->ads->stepActive($rules, AdStep::BeforeSurvey) || $this->ads->stepActive($rules, AdStep::BeforePublish)) {
            $text .= ' You choose whether to watch each ad and can close it at any time; the next step then stays locked.';
        }

        if (($rules['survey_enabled'] ?? false) && ! ($rules['award_survey_points'] ?? false)) {
            $text .= ' A survey completed here unlocks your quote and does not earn points.';
        }

        return $text;
    }

    /**
     * @return list<array{key: string, label: string, status: string}>
     */
    private function steps(QuoteWorkflow $workflow): array
    {
        $state = $workflow->state;
        $rules = $workflow->settings_snapshot;

        return array_values(array_filter([
            $this->step(
                'ad_before_survey',
                'Watch an ad',
                $workflow->ad1_result,
                $this->ads->stepActive($rules, AdStep::BeforeSurvey),
                $state === WorkflowState::AdBeforeSurvey,
            ),
            $this->step(
                'survey',
                'Complete a survey',
                $workflow->survey_result,
                (bool) $workflow->rule('survey_enabled'),
                $state === WorkflowState::Survey,
            ),
            $this->step(
                'generate',
                'Create the quote',
                $workflow->quote_id === null ? null : 'completed',
                true,
                $state === WorkflowState::Generate || $state === WorkflowState::Generating,
            ),
            $this->step(
                'ad_before_publish',
                'Watch an ad',
                $workflow->ad2_result,
                $this->ads->stepActive($rules, AdStep::BeforePublish),
                $state === WorkflowState::AdBeforePublish,
            ),
        ]));
    }

    /**
     * @return array{key: string, label: string, status: string}|null Null for a step that is switched off.
     */
    private function step(string $key, string $label, ?string $result, bool $active, bool $current): ?array
    {
        if ($result === 'disabled' || ($result === null && ! $active)) {
            return null;
        }

        $status = match (true) {
            $result !== null && str_starts_with($result, 'skipped') => 'skipped',
            $result !== null => 'done',
            $current => 'current',
            default => 'upcoming',
        };

        return ['key' => $key, 'label' => $label, 'status' => $status];
    }
}
