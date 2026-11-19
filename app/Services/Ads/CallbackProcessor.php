<?php

namespace App\Services\Ads;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\CallbackResult;
use App\Enums\RewardStatus;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\ProviderCallback;
use App\Services\Ads\Contracts\AdProviderAdapter;
use App\Services\Ads\Data\CallbackPayload;
use App\Services\Ads\Data\ProcessedCallback;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The provider-neutral confirmation pipeline. A verified provider callback is the only thing that can approve
 * a transaction, record its revenue or award points:
 *
 *   authenticate -> log -> de-duplicate by the provider's transaction id -> match the activity ->
 *   record revenue/payout separately -> approve or reject -> apply the configured reward
 *
 * Every callback ends up in `provider_callbacks`, valid or not. Retries and concurrent duplicates are safe:
 * the unique provider transaction id (and the unique ledger key) make a second pass a no-op.
 */
class CallbackProcessor
{
    public function __construct(
        private readonly RewardService $rewards,
        private readonly UserRevenueShare $revenueShare,
        private readonly ProviderIdentities $identities,
    ) {}

    public function handle(AdProvider $provider, AdProviderAdapter $adapter, Request $request): ProcessedCallback
    {
        $payload = $adapter->parseCallback($provider, $request);

        if ($payload === null) {
            $this->log($provider, $request, null, CallbackResult::Error, false, 'empty_callback');

            return new ProcessedCallback(400, CallbackResult::Error);
        }

        if (! $payload->signatureValid) {
            $this->log($provider, $request, $payload, CallbackResult::InvalidSignature, false, 'signature_mismatch');

            return new ProcessedCallback(401, CallbackResult::InvalidSignature);
        }

        if (! $payload->tokenValid) {
            $this->log($provider, $request, $payload, CallbackResult::InvalidToken, true, 'api_token_mismatch');

            return new ProcessedCallback(401, CallbackResult::InvalidToken);
        }

        if ($payload->transactionId === null) {
            $this->log($provider, $request, $payload, CallbackResult::Error, true, 'missing_transaction_id');

            return new ProcessedCallback(400, CallbackResult::Error);
        }

        try {
            return DB::transaction(fn (): ProcessedCallback => $this->process($provider, $request, $payload), 3);
        } catch (UniqueConstraintViolationException) {
            // A concurrent delivery of the same transaction won the race and committed first.
            $existing = AdActivity::query()
                ->where('provider_key', $provider->key)
                ->where('provider_transaction_id', $payload->transactionId)
                ->first();

            $this->log($provider, $request, $payload, CallbackResult::Duplicate, true, 'concurrent_duplicate', $existing);

            return new ProcessedCallback(200, CallbackResult::Duplicate, $existing?->id);
        }
    }

    private function process(AdProvider $provider, Request $request, CallbackPayload $payload): ProcessedCallback
    {
        $existing = AdActivity::query()
            ->where('provider_key', $provider->key)
            ->where('provider_transaction_id', $payload->transactionId)
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            $this->log($provider, $request, $payload, CallbackResult::Duplicate, true, 'already_processed', $existing);

            return new ProcessedCallback(200, CallbackResult::Duplicate, $existing->id);
        }

        $resolvesActivity = $payload->kind !== CallbackPayload::KIND_OTHER;
        [$activity, $unmatchedNote] = $resolvesActivity ? $this->match($provider, $payload) : [null, 'not_a_survey_payout'];

        if ($activity === null) {
            $activity = $this->createOrphan($provider, $payload);
            $result = $resolvesActivity ? CallbackResult::Unmatched : CallbackResult::Recorded;
            $this->log($provider, $request, $payload, $result, true, $unmatchedNote, $activity);

            return new ProcessedCallback(200, $result, $activity->id);
        }

        $this->applyConfirmation($activity, $payload);

        if ($payload->kind === CallbackPayload::KIND_COMPLETION) {
            $activity->forceFill(['status' => ActivityStatus::Approved])->save();
            $status = $this->rewards->award($activity);
            // Option B sandbox: floor(1/3) of verified publisher revenue → pending wallet (toggle-gated).
            $this->revenueShare->allocateIfEnabled($activity);
            $result = $status === RewardStatus::Credited ? CallbackResult::Credited : CallbackResult::NotRewarded;
            $note = $activity->reward_note;
        } else {
            $activity->forceFill(['status' => ActivityStatus::Rejected])->save();
            $this->rewards->decline($activity, 'survey_outcome_'.$payload->outcome);
            $result = CallbackResult::UnsuccessfulOutcome;
            $note = 'survey_outcome_'.$payload->outcome;
        }

        $this->log($provider, $request, $payload, $result, true, $note, $activity);

        return new ProcessedCallback(200, $result, $activity->id);
    }

    /**
     * Find the transaction this callback belongs to: first by the activity id we passed through to the
     * provider, then by the user's opaque id plus the survey id.
     *
     * @return array{0: AdActivity|null, 1: string|null} the activity (or null) and why it did not match
     */
    private function match(AdProvider $provider, CallbackPayload $payload): array
    {
        if ($payload->activityId !== null) {
            $activity = AdActivity::query()
                ->where('id', $payload->activityId)
                ->where('provider_key', $provider->key)
                ->lockForUpdate()
                ->first();

            if ($activity === null) {
                return [null, 'activity_not_found'];
            }

            if ($activity->provider_transaction_id !== null) {
                // The attempt was already settled by a different provider transaction: this is a further payout.
                return [null, 'activity_already_confirmed'];
            }

            if ($payload->externalUserId !== null && $activity->provider_user_id !== null && $payload->externalUserId !== $activity->provider_user_id) {
                return [null, 'user_mismatch'];
            }

            return [$activity, null];
        }

        if ($payload->externalUserId === null || $this->identities->userIdFor($provider->key, $payload->externalUserId) === null) {
            return [null, 'no_activity_reference'];
        }

        $query = AdActivity::query()
            ->where('provider_key', $provider->key)
            ->where('provider_user_id', $payload->externalUserId)
            ->whereNull('provider_transaction_id')
            ->where('source', AdActivity::SOURCE_APP)
            ->whereIn('status', [...ActivityStatus::pendingValues(), ActivityStatus::Expired->value]);

        if ($payload->offerIdentifier !== null) {
            $query->where('offer_identifier', $payload->offerIdentifier);
        }

        $activity = $query->orderByDesc('survey_started_at')->lockForUpdate()->first();

        return $activity === null ? [null, 'no_open_activity'] : [$activity, null];
    }

    /**
     * A signed callback we could not attach to a started survey (or that is not a survey payout) still happened
     * and still carries revenue, so it is stored as its own transaction. It never earns points.
     */
    private function createOrphan(AdProvider $provider, CallbackPayload $payload): AdActivity
    {
        $userId = $payload->externalUserId === null
            ? null
            : $this->identities->userIdFor($provider->key, $payload->externalUserId);

        $approved = $payload->kind === CallbackPayload::KIND_COMPLETION;
        $rejected = $payload->kind === CallbackPayload::KIND_REJECTED;

        $activity = new AdActivity([
            'user_id' => $userId,
            'source' => AdActivity::SOURCE_POSTBACK_ONLY,
            'provider_key' => $provider->key,
            'provider_user_id' => $payload->externalUserId,
            'activity_type' => ActivityType::Survey->value,
            'offer_identifier' => $payload->offerIdentifier,
            'status' => $approved ? ActivityStatus::Approved : ($rejected ? ActivityStatus::Rejected : ActivityStatus::Approved),
            'reward_status' => RewardStatus::NotEligible,
            'reward_note' => $payload->kind === CallbackPayload::KIND_OTHER ? 'not_a_survey_payout' : 'unmatched_activity',
        ]);

        $this->applyConfirmation($activity, $payload);
        $activity->save();

        return $activity;
    }

    /**
     * Store what the provider told us. Publisher revenue and the user-facing payout are kept in separate columns.
     */
    private function applyConfirmation(AdActivity $activity, CallbackPayload $payload): void
    {
        $activity->forceFill([
            'provider_transaction_id' => $payload->transactionId,
            'provider_confirmed_at' => now(),
            'survey_outcome' => $payload->outcome,
            'payout_type' => $payload->payoutType,
            'provider_payout_amount' => $payload->payoutAmount,
            'payout_currency' => $payload->payoutCurrency,
            'publisher_revenue_micros' => $payload->revenueMicros,
            'currency' => 'USD',
            'callback_verification_status' => 'verified',
            'callback_payload' => $payload->sanitized,
        ]);

        if ($activity->exists) {
            $activity->save();
        }
    }

    private function log(
        AdProvider $provider,
        Request $request,
        ?CallbackPayload $payload,
        CallbackResult $result,
        bool $signatureValid,
        ?string $note = null,
        ?AdActivity $activity = null,
    ): void {
        ProviderCallback::query()->create([
            'provider_key' => $provider->key,
            'activity_id' => $activity?->id,
            'provider_transaction_id' => $payload?->transactionId,
            'signature_valid' => $signatureValid,
            'result' => $result,
            'note' => $note,
            // Revenue from an unauthenticated request is never counted.
            'revenue_micros' => $signatureValid && $payload !== null ? $payload->revenueMicros : 0,
            'payload' => $payload?->sanitized,
            'ip' => $request->ip(),
            'received_at' => now(),
        ]);
    }
}
