<?php

namespace App\Services\QuoteWorkflow;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\AdAttemptStatus;
use App\Enums\AdStep;
use App\Enums\CallOutcome;
use App\Enums\FallbackReason;
use App\Enums\GatePurpose;
use App\Enums\PublishMode;
use App\Enums\QuoteStatus;
use App\Enums\WorkflowState;
use App\Models\AdActivity;
use App\Models\AdProviderCall;
use App\Models\AdRequest;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Models\QuoteWorkflow;
use App\Models\QuoteWorkflowSetting;
use App\Models\RewardedAdAttempt;
use App\Models\User;
use App\Services\Ads\AdGate;
use App\Services\Ads\AdProviderManager;
use App\Services\Ads\Data\OfferBundle;
use App\Services\QuoteModeration;
use App\Services\QuoteRouter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The quote workflow: rewarded ad -> survey -> generate -> rewarded ad -> publish.
 *
 * The server owns the state. The browser can only report what happened to an ad or ask for the next thing; every
 * move is a conditional update from the expected state, so two requests racing each other cannot both advance a
 * run. Steps that are switched off are skipped.
 */
class WorkflowService
{
    /** A generation that has not finished after this long is assumed to have died and may be retried. */
    private const STALE_GENERATION_MINUTES = 5;

    /** While a survey started this recently is still unconfirmed, an empty survey list does not skip the step. */
    private const PENDING_SURVEY_MINUTES = 30;

    public function __construct(
        private readonly AdGate $gate,
        private readonly RewardedAdService $ads,
        private readonly StepPolicy $policy,
        private readonly QuoteRouter $router,
        private readonly QuoteModeration $moderation,
        private readonly AdProviderManager $providers,
        private readonly WorkflowLogger $logger,
    ) {}

    /**
     * The user's unfinished run, brought up to date, or null.
     */
    public function activeFor(User $user): ?QuoteWorkflow
    {
        $workflow = QuoteWorkflow::query()->where('active_user_id', $user->id)->first();

        if ($workflow === null) {
            return null;
        }

        $workflow = $this->settle($workflow);

        return $workflow->state->isActive() ? $workflow : null;
    }

    /**
     * Start a run, or resume the one already in progress (a user has at most one).
     *
     * @throws ValidationException when the workflow is switched off
     */
    public function start(User $user, QuoteCategory $category): QuoteWorkflow
    {
        $existing = $this->activeFor($user);

        if ($existing !== null) {
            return $existing;
        }

        $settings = QuoteWorkflowSetting::current();

        if (! $settings->enabled) {
            throw ValidationException::withMessages(['workflow' => 'The step-by-step quote flow is switched off.']);
        }

        try {
            $workflow = QuoteWorkflow::query()->create([
                'user_id' => $user->id,
                'quote_category_id' => $category->id,
                'state' => WorkflowState::AdBeforeSurvey,
                'settings_snapshot' => $settings->snapshot(),
                'active_user_id' => $user->id,
                'expires_at' => now()->addHours($settings->expires_after_hours),
            ]);
        } catch (UniqueConstraintViolationException) {
            // A second tab started one at the same moment.
            return QuoteWorkflow::query()->where('active_user_id', $user->id)->firstOrFail();
        }

        $this->logger->log(WorkflowLogger::WORKFLOW, 'started', $workflow, ['category_id' => $category->id]);

        return $this->settle($workflow);
    }

    /**
     * Move a run past every step that is already satisfied or switched off. Safe to call at any time.
     */
    public function settle(QuoteWorkflow $workflow): QuoteWorkflow
    {
        $workflow->refresh();

        if ($workflow->state->isBeforeGeneration() && $workflow->expires_at?->isPast() === true) {
            $this->transition($workflow, $workflow->state, WorkflowState::Expired);

            return $workflow;
        }

        // Each pass moves forward at most one state, and there are fewer states than passes.
        for ($pass = 0; $pass < 6; $pass++) {
            $moved = match ($workflow->state) {
                WorkflowState::AdBeforeSurvey => $this->settleFirstAd($workflow),
                WorkflowState::Survey => $this->settleSurvey($workflow),
                WorkflowState::Generating => $this->recoverStaleGeneration($workflow),
                WorkflowState::AdBeforePublish => $this->settlePublishStep($workflow),
                default => false,
            };

            if (! $moved) {
                break;
            }
        }

        return $workflow;
    }

    /**
     * Prepare the rewarded ad for the current step. When no ad unit is configured the step is treated as
     * "no ad available" and the administrator's rule decides.
     *
     * @return array{attempt: RewardedAdAttempt|null, unit: array{placement_id: int|null, ad_unit_path: string, load_timeout_seconds: int, min_watch_seconds: int, test_mode: bool}|null, message: string|null}
     */
    public function prepareAd(QuoteWorkflow $workflow, ?string $ip, ?string $userAgent): array
    {
        $workflow = $this->settle($workflow);
        $step = AdStep::forState($workflow->state);

        if ($step === null) {
            return ['attempt' => null, 'unit' => null, 'message' => null];
        }

        $unit = $this->ads->unitFor($workflow, $step);

        if ($unit === null) {
            $this->logger->log(WorkflowLogger::AD, 'not_configured', $workflow, ['step' => $step->value], 'warning');

            // A missing unit is a setup problem, not something the user did, so it does not use up their limit.
            if (! $this->policy->mayContinue($workflow, FallbackReason::AdUnavailable, $step)) {
                return ['attempt' => null, 'unit' => null, 'message' => FallbackReason::AdUnavailable->blockedMessage()];
            }

            $this->completeAdStep($workflow, $step, 'skipped_not_configured');
            $this->settle($workflow);

            return ['attempt' => null, 'unit' => null, 'message' => null];
        }

        $attempt = $this->ads->issue($workflow, $step, $unit, $ip, $userAgent);
        $this->logger->log(WorkflowLogger::AD, 'issued', $workflow, ['step' => $step->value, 'attempt' => $attempt->id, 'ad_unit' => $unit['ad_unit_path']]);

        return ['attempt' => $attempt, 'unit' => $unit, 'message' => null];
    }

    /**
     * Apply one browser-reported ad event to the attempt and, when it settles the step, to the run.
     *
     * @return array{attempt: RewardedAdAttempt, message: string|null}
     *
     * @throws ValidationException when the event is not acceptable
     */
    public function handleAdEvent(QuoteWorkflow $workflow, RewardedAdAttempt $attempt, string $event, ?string $detail = null): array
    {
        $attempt = $this->ads->record($attempt, $event, $detail);
        $step = $attempt->step;
        $workflow->refresh();

        $failed = in_array($attempt->status, [AdAttemptStatus::Unavailable, AdAttemptStatus::Error], true);

        $this->logger->log(WorkflowLogger::AD, $event, $workflow, array_filter([
            'step' => $step->value,
            'attempt' => $attempt->id,
            'detail' => $detail,
        ]), $failed ? 'warning' : 'info');

        // The run may already have moved on, for example "closed" arriving after the reward.
        if ($workflow->state !== $step->state()) {
            return ['attempt' => $attempt, 'message' => null];
        }

        $reason = match ($attempt->status) {
            AdAttemptStatus::Dismissed => FallbackReason::AdDismissed,
            AdAttemptStatus::Unavailable => FallbackReason::AdUnavailable,
            AdAttemptStatus::Error => FallbackReason::AdError,
            default => null,
        };

        $message = null;

        if ($attempt->status === AdAttemptStatus::Granted) {
            $this->completeAdStep($workflow, $step, 'completed');
        } elseif ($reason !== null) {
            $message = $this->fallBack($workflow, $reason, $step);
        }

        $this->settle($workflow);

        return ['attempt' => $attempt, 'message' => $message];
    }

    /**
     * Ask the allowed providers for surveys. When none can be shown, the administrator's rule decides whether
     * the user may go on without one.
     *
     * @return array{bundle: OfferBundle|null, message: string|null}
     */
    public function surveyOffers(QuoteWorkflow $workflow, string $ip, int $deviceType): array
    {
        $workflow = $this->settle($workflow);

        if ($workflow->state !== WorkflowState::Survey) {
            return ['bundle' => null, 'message' => null];
        }

        /** @var list<string> $allowed */
        $allowed = array_values((array) $workflow->rule('survey_provider_keys'));

        $bundle = $this->providers->requestOffers($workflow->user()->firstOrFail(), ActivityType::Survey, $ip, $deviceType, $allowed);

        if ($bundle->hasOffers()) {
            $this->logger->log(WorkflowLogger::SURVEY, 'offers_loaded', $workflow, [
                'provider' => $bundle->providerKey,
                'offers' => count($bundle->offers),
                'request_id' => $bundle->request->id,
            ]);

            return ['bundle' => $bundle, 'message' => null];
        }

        $reason = $this->classifyEmptyRequest($bundle->request);
        $this->logger->log(WorkflowLogger::SURVEY, $reason->value, $workflow, ['request_id' => $bundle->request->id], 'warning');

        // The user may be waiting for a survey they just finished; do not jump over it.
        if ($this->hasRecentPendingSurvey($workflow)) {
            return ['bundle' => $bundle, 'message' => null];
        }

        $message = $this->fallBack($workflow, $reason);
        $this->settle($workflow);

        return ['bundle' => $bundle, 'message' => $message];
    }

    /**
     * Generate the quote. It stays hidden until the last step is done.
     *
     * @throws ValidationException when the run is not ready or generation fails
     */
    public function generate(QuoteWorkflow $workflow): QuoteWorkflow
    {
        $workflow = $this->settle($workflow);

        if ($workflow->state !== WorkflowState::Generate || ! $this->transition($workflow, WorkflowState::Generate, WorkflowState::Generating)) {
            throw ValidationException::withMessages(['generation' => 'This run is not ready to create a quote.']);
        }

        $user = $workflow->user()->firstOrFail();
        $unlock = null;

        if ($workflow->survey_result === 'completed') {
            $unlock = $this->gate->claim($user, GatePurpose::QuoteGeneration);

            if ($unlock === null) {
                $this->transition($workflow, WorkflowState::Generating, WorkflowState::Survey, ['survey_result' => null]);

                throw ValidationException::withMessages(['ad_gate' => 'Complete a survey first to create a quote.']);
            }

            $workflow->update(['survey_activity_id' => $unlock->id]);
        }

        try {
            $quote = $this->router->generate($user, $workflow->category()->firstOrFail(), QuoteStatus::AwaitingUnlock);
        } catch (Throwable $exception) {
            // Our failure never costs the user their survey.
            if ($unlock !== null) {
                $this->gate->release($unlock);
            }

            $this->transition($workflow, WorkflowState::Generating, WorkflowState::Generate, ['survey_activity_id' => null]);
            $this->logger->log(WorkflowLogger::GENERATION, 'failed', $workflow, [
                'error' => $exception instanceof ValidationException ? $exception->getMessage() : $exception::class,
            ], 'error');

            throw $exception;
        }

        if ($unlock !== null) {
            $this->gate->attach($unlock, $quote);
        }

        $this->transition($workflow, WorkflowState::Generating, WorkflowState::AdBeforePublish, ['quote_id' => $quote->id]);
        $this->logger->log(WorkflowLogger::GENERATION, 'succeeded', $workflow, ['quote_id' => $quote->id]);

        return $this->settle($workflow);
    }

    /**
     * Give up on a run that has no quote yet.
     *
     * @throws ValidationException once a quote exists
     */
    public function cancel(QuoteWorkflow $workflow): QuoteWorkflow
    {
        $workflow->refresh();

        if (! $workflow->state->isBeforeGeneration() || ! $this->transition($workflow, $workflow->state, WorkflowState::Cancelled)) {
            throw ValidationException::withMessages(['workflow' => 'This run can no longer be cancelled.']);
        }

        $this->logger->log(WorkflowLogger::WORKFLOW, 'cancelled', $workflow);

        return $workflow;
    }

    /**
     * An administrator releases a quote that is waiting for the last ad.
     *
     * @throws ValidationException when there is nothing waiting
     */
    public function unlockByAdministrator(QuoteWorkflow $workflow, User $administrator): QuoteWorkflow
    {
        $workflow->refresh();

        if (! $this->finish($workflow, 'manual_override')) {
            throw ValidationException::withMessages(['workflow' => 'This run has no quote waiting to be unlocked.']);
        }

        $this->logger->log(WorkflowLogger::ADMIN, 'quote_unlocked', $workflow, ['administrator_id' => $administrator->id]);

        return $workflow;
    }

    private function settleFirstAd(QuoteWorkflow $workflow): bool
    {
        if ($this->ads->stepActive($workflow->settings_snapshot, AdStep::BeforeSurvey)) {
            return false;
        }

        return $this->completeAdStep($workflow, AdStep::BeforeSurvey, 'disabled');
    }

    private function settleSurvey(QuoteWorkflow $workflow): bool
    {
        if (! $workflow->rule('survey_enabled')) {
            return $this->transition($workflow, WorkflowState::Survey, WorkflowState::Generate, ['survey_result' => 'disabled']);
        }

        // Only a verified provider callback makes an unlock; one left over from an earlier run counts too.
        $user = $workflow->user()->firstOrFail();

        if ($this->gate->available($user, GatePurpose::QuoteGeneration) > 0) {
            return $this->transition($workflow, WorkflowState::Survey, WorkflowState::Generate, ['survey_result' => 'completed']);
        }

        $latest = $this->latestSurvey($workflow);

        if ($latest === null || $latest->status !== ActivityStatus::Rejected
            || $this->policy->refusal($workflow, FallbackReason::Disqualified) !== null) {
            return false;
        }

        return $this->skipSurvey($workflow, FallbackReason::Disqualified);
    }

    private function recoverStaleGeneration(QuoteWorkflow $workflow): bool
    {
        if ($workflow->updated_at === null || $workflow->updated_at->gt(now()->subMinutes(self::STALE_GENERATION_MINUTES))) {
            return false;
        }

        $unlock = $workflow->survey_activity_id === null ? null : AdActivity::query()->find($workflow->survey_activity_id);

        if (! $this->transition($workflow, WorkflowState::Generating, WorkflowState::Generate, ['survey_activity_id' => null])) {
            return false;
        }

        if ($unlock !== null && $unlock->consumed_quote_id === null) {
            $this->gate->release($unlock);
        }

        $this->logger->log(WorkflowLogger::GENERATION, 'recovered_stale', $workflow, [], 'warning');

        // Stop here: the user starts the generation again.
        return false;
    }

    private function settlePublishStep(QuoteWorkflow $workflow): bool
    {
        $quote = $workflow->quote_id === null ? null : Quote::query()->find($workflow->quote_id);

        if ($quote === null) {
            return $this->transition($workflow, WorkflowState::AdBeforePublish, WorkflowState::Cancelled);
        }

        // A moderator already decided what happens to this quote.
        if ($quote->status !== QuoteStatus::AwaitingUnlock) {
            return $this->finish($workflow, 'manual_override');
        }

        if ($this->ads->stepActive($workflow->settings_snapshot, AdStep::BeforePublish)) {
            return false;
        }

        return $this->finish($workflow, 'disabled');
    }

    /**
     * Move on, or explain why the user stays on this step.
     */
    private function fallBack(QuoteWorkflow $workflow, FallbackReason $reason, ?AdStep $step = null): ?string
    {
        $refusal = $this->policy->refusal($workflow, $reason, $step);

        if ($refusal !== null) {
            $this->logger->log($step === null ? WorkflowLogger::SURVEY : WorkflowLogger::AD, 'step_blocked', $workflow, [
                'reason' => $reason->value,
                'step' => $step === null ? 'survey' : $step->value,
            ], 'warning');

            return $refusal;
        }

        if ($step === null) {
            $this->skipSurvey($workflow, $reason);
        } elseif ($this->completeAdStep($workflow, $step, $reason->skippedResult())) {
            $this->logSkip($workflow, $reason, $step->value);
        }

        return null;
    }

    private function skipSurvey(QuoteWorkflow $workflow, FallbackReason $reason): bool
    {
        $moved = $this->transition($workflow, WorkflowState::Survey, WorkflowState::Generate, ['survey_result' => $reason->skippedResult()]);

        if ($moved) {
            $this->logSkip($workflow, $reason, 'survey');
        }

        return $moved;
    }

    private function logSkip(QuoteWorkflow $workflow, FallbackReason $reason, string $step): void
    {
        $this->logger->log(WorkflowLogger::WORKFLOW, WorkflowLogger::FALLBACK_EVENT, $workflow, [
            'reason' => $reason->value,
            'step' => $step,
        ], 'warning');
    }

    private function completeAdStep(QuoteWorkflow $workflow, AdStep $step, string $result): bool
    {
        return match ($step) {
            AdStep::BeforeSurvey => $this->transition($workflow, WorkflowState::AdBeforeSurvey, WorkflowState::Survey, ['ad1_result' => $result]),
            AdStep::BeforePublish => $this->finish($workflow, $result),
        };
    }

    /**
     * The last step is done: release the quote and publish it when the rules say so.
     */
    private function finish(QuoteWorkflow $workflow, string $result): bool
    {
        return DB::transaction(function () use ($workflow, $result): bool {
            if (! $this->transition($workflow, WorkflowState::AdBeforePublish, WorkflowState::Completed, ['ad2_result' => $result])) {
                return false;
            }

            $quote = $workflow->quote_id === null ? null : Quote::query()->find($workflow->quote_id);

            if ($quote === null || $quote->status !== QuoteStatus::AwaitingUnlock) {
                return true;
            }

            $this->moderation->approve($quote);

            if ($workflow->rule('publish_mode') !== PublishMode::Auto->value) {
                $this->logger->log(WorkflowLogger::PUBLISH, 'unlocked', $workflow, ['quote_id' => $quote->id]);

                return true;
            }

            try {
                $this->moderation->publish($quote);
                $this->logger->log(WorkflowLogger::PUBLISH, 'published', $workflow, ['quote_id' => $quote->id]);
            } catch (ValidationException $exception) {
                // The moderation rules sent it to review instead.
                $this->logger->log(WorkflowLogger::PUBLISH, 'held_for_review', $workflow, ['quote_id' => $quote->id], 'warning');
            }

            return true;
        });
    }

    /**
     * Move from one state to another only if the run is still where we think it is.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function transition(QuoteWorkflow $workflow, WorkflowState $from, WorkflowState $to, array $attributes = []): bool
    {
        $changes = [...$attributes, 'state' => $to->value, 'updated_at' => now()];

        if (! $to->isActive()) {
            $changes['active_user_id'] = null;
            $changes['expires_at'] = null;
            $changes['completed_at'] = now();
        }

        // A run that holds a quote never expires.
        if ($to === WorkflowState::AdBeforePublish) {
            $changes['expires_at'] = null;
        }

        $moved = QuoteWorkflow::query()
            ->whereKey($workflow->id)
            ->where('state', $from->value)
            ->update($changes) === 1;

        $workflow->refresh();

        if ($moved) {
            $this->logger->log(WorkflowLogger::WORKFLOW, 'state_changed', $workflow, ['from' => $from->value, 'to' => $to->value]);
        }

        return $moved;
    }

    private function latestSurvey(QuoteWorkflow $workflow): ?AdActivity
    {
        // ULID keys sort by creation time.
        return $workflow->surveys()->orderByDesc('id')->first();
    }

    private function hasRecentPendingSurvey(QuoteWorkflow $workflow): bool
    {
        return $workflow->surveys()
            ->whereIn('status', ActivityStatus::pendingValues())
            ->where('survey_started_at', '>=', now()->subMinutes(self::PENDING_SURVEY_MINUTES))
            ->exists();
    }

    /**
     * Whether the providers had nothing for this user, or could not be asked at all.
     */
    private function classifyEmptyRequest(AdRequest $request): FallbackReason
    {
        $outcomes = AdProviderCall::query()->where('ad_request_id', $request->id)->pluck('outcome');
        $values = $outcomes->map(fn (mixed $outcome): string => $outcome instanceof CallOutcome ? $outcome->value : (string) $outcome);

        $nobodyAnswered = $values->every(fn (string $value): bool => $value === CallOutcome::Skipped->value);

        return $nobodyAnswered || $values->contains(CallOutcome::Failed->value)
            ? FallbackReason::SurveyApiError
            : FallbackReason::NoSurveys;
    }
}
