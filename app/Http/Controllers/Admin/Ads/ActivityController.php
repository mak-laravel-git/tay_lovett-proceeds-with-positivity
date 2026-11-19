<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Enums\ActivityStatus;
use App\Enums\RewardStatus;
use App\Http\Controllers\Controller;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdSetting;
use App\Models\ProviderCallback;
use App\Support\Micros;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every survey transaction across all users and providers, with a per-transaction audit timeline.
 */
class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'user' => ['nullable', 'integer'],
            'survey' => ['nullable', 'string', 'max:191'],
            'provider' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(ActivityStatus::cases(), 'value'))],
            'reward_status' => ['nullable', 'string', 'in:'.implode(',', array_column(RewardStatus::cases(), 'value'))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $timezone = AdSetting::timezoneOrDefault();
        $appTimezone = (string) config('app.timezone');

        $query = AdActivity::query()->with('user:id,name,email');

        $query->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
            $builder->where(function (Builder $inner) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $inner->where('offer_identifier', 'like', $like)
                    ->orWhere('provider_transaction_id', 'like', $like)
                    ->orWhere('id', 'like', $like)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });
        });
        $query->when($filters['user'] ?? null, fn (Builder $builder, int|string $user) => $builder->where('user_id', $user));
        $query->when($filters['survey'] ?? null, fn (Builder $builder, string $survey) => $builder->where('offer_identifier', $survey));
        $query->when($filters['provider'] ?? null, fn (Builder $builder, string $provider) => $builder->where('provider_key', $provider));
        $query->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status));
        $query->when($filters['reward_status'] ?? null, fn (Builder $builder, string $status) => $builder->where('reward_status', $status));
        $query->when($filters['from'] ?? null, fn (Builder $builder, string $from) => $builder->where(
            'created_at', '>=', CarbonImmutable::parse($from, $timezone)->startOfDay()->setTimezone($appTimezone),
        ));
        $query->when($filters['to'] ?? null, fn (Builder $builder, string $to) => $builder->where(
            'created_at', '<', CarbonImmutable::parse($to, $timezone)->addDay()->startOfDay()->setTimezone($appTimezone),
        ));

        $activities = $query->latest('created_at')->latest('id')->paginate(25)->withQueryString()->through(fn (AdActivity $activity): array => [
            'id' => $activity->id,
            'user' => $activity->user === null ? null : ['id' => $activity->user->id, 'name' => $activity->user->name, 'email' => $activity->user->email],
            'provider' => $activity->provider_key,
            'survey' => $activity->offer_identifier,
            'source' => $activity->source,
            'started_at' => $activity->survey_started_at?->toIso8601String(),
            'confirmed_at' => $activity->provider_confirmed_at?->toIso8601String(),
            'status' => $activity->status->value,
            'publisher_revenue_usd' => Micros::toDollars($activity->publisher_revenue_micros),
            'points' => $activity->awarded_points,
            'reward_status' => $activity->reward_status->value,
        ]);

        return Inertia::render('admin/ads/activity', [
            'activities' => $activities,
            'filters' => array_filter($filters, fn ($value) => $value !== null && $value !== ''),
            'options' => [
                'providers' => AdProvider::query()->orderBy('priority')->get(['key', 'label'])->map(fn (AdProvider $provider): array => ['value' => $provider->key, 'label' => $provider->label])->all(),
                'statuses' => ActivityStatus::options(),
                'reward_statuses' => RewardStatus::options(),
            ],
            'timezone' => $timezone,
        ]);
    }

    public function show(AdActivity $activity): Response
    {
        $activity->load(['user:id,name,email', 'callbacks', 'pointEntries', 'request.calls']);

        $callbacks = $activity->callbacks;
        $firstCallback = $callbacks->first();
        $authentic = $callbacks->first(fn (ProviderCallback $callback): bool => $callback->signature_valid);

        return Inertia::render('admin/ads/activity-show', [
            'activity' => [
                'id' => $activity->id,
                'source' => $activity->source,
                'user' => $activity->user === null ? null : ['id' => $activity->user->id, 'name' => $activity->user->name, 'email' => $activity->user->email],
                'provider' => $activity->provider_key,
                'provider_user_id' => $activity->provider_user_id,
                'survey' => $activity->offer_identifier,
                'survey_length_minutes' => $activity->offer_length_minutes,
                'survey_category' => $activity->offer_category,
                'provider_transaction_id' => $activity->provider_transaction_id,
                'status' => $activity->status->value,
                'reward_status' => $activity->reward_status->value,
                'reward_note' => $activity->reward_note,
                'provider_status' => $activity->provider_status,
                'survey_outcome' => $activity->survey_outcome,
                'payout_type' => $activity->payout_type,
                'provider_payout_amount' => $activity->provider_payout_amount,
                'payout_currency' => $activity->payout_currency,
                // Publisher revenue and the user's reward are deliberately separate figures.
                'publisher_revenue_usd' => Micros::toDollars($activity->publisher_revenue_micros),
                'publisher_revenue_micros' => $activity->publisher_revenue_micros,
                'awarded_points' => $activity->awarded_points,
                'callback_verification_status' => $activity->callback_verification_status,
                'callback_payload' => $activity->callback_payload,
                'error_code' => $activity->error_code,
                'error_message' => $activity->error_message,
                'created_at' => $activity->created_at?->toIso8601String(),
                'updated_at' => $activity->updated_at?->toIso8601String(),
            ],
            'timezone' => AdSetting::timezoneOrDefault(),
            'timeline' => $this->timeline($activity, $firstCallback, $authentic),
            'callbacks' => $callbacks->map(fn (ProviderCallback $callback): array => [
                'id' => $callback->id,
                'received_at' => $callback->received_at->toIso8601String(),
                'result' => $callback->result->value,
                'result_label' => $callback->result->label(),
                'signature_valid' => $callback->signature_valid,
                'note' => $callback->note,
                'revenue_usd' => Micros::toDollars($callback->revenue_micros),
                'ip' => $callback->ip,
                'payload' => $callback->payload,
            ])->all(),
            'ledger' => $activity->pointEntries->map(fn ($entry): array => [
                'id' => $entry->id,
                'points' => $entry->points,
                'key' => $entry->idempotency_key,
                'description' => $entry->description,
                'at' => $entry->created_at->toIso8601String(),
            ])->all(),
            // A transaction that only exists because of a postback has no originating survey-list request.
            'provider_calls' => ($activity->ad_request_id === null ? collect() : $activity->request->calls)->map(fn ($call): array => [
                'provider' => $call->provider_key,
                'outcome' => $call->outcome->value,
                'error' => $call->error_category,
                'http_status' => $call->http_status,
                'latency_ms' => $call->latency_ms,
                'position' => $call->position,
            ])->values()->all(),
        ]);
    }

    /**
     * The audit trail an administrator reads top to bottom. A step that has not happened yet is shown as
     * pending, never invented.
     *
     * @return list<array{key: string, label: string, at: string|null, state: string, detail: string|null}>
     */
    private function timeline(AdActivity $activity, ?ProviderCallback $first, ?ProviderCallback $authentic): array
    {
        $approved = $activity->status === ActivityStatus::Approved;
        $rejected = $activity->status === ActivityStatus::Rejected;
        $appSourced = $activity->source === AdActivity::SOURCE_APP;

        $steps = [];

        if ($appSourced) {
            $steps[] = $this->step('loaded', 'Survey loaded', $activity->survey_loaded_at?->toIso8601String());
            $steps[] = $this->step('started', 'User started survey', $activity->survey_started_at?->toIso8601String());
            $steps[] = $this->step('returned', 'User returned', $activity->survey_returned_at?->toIso8601String(), 'The browser came back; this alone approves nothing.');
        } else {
            $steps[] = $this->step('unmatched', 'No survey start recorded', null, 'The provider confirmed a payout we could not tie to a survey started in this app.', 'skipped');
        }

        $steps[] = $this->step('postback', 'Provider postback received', $first?->received_at->toIso8601String(), 'Time our server received it; the provider sends no timestamp of its own.');
        $steps[] = $this->step(
            'signature',
            'Signature verified',
            $authentic?->received_at->toIso8601String(),
            $first !== null && $authentic === null ? 'Signature did not verify.' : null,
            $first !== null && $authentic === null ? 'failed' : null,
        );
        $steps[] = $this->step(
            'confirmed',
            $rejected ? 'Provider reported the survey as not completed' : 'Survey completion confirmed',
            $activity->provider_confirmed_at?->toIso8601String(),
            $rejected ? 'Outcome '.$activity->survey_outcome.' (disqualified / over quota): revenue recorded, no points.' : null,
            $rejected ? 'skipped' : null,
        );

        $calculated = $approved || $rejected;
        $steps[] = $this->step(
            'calculated',
            'User reward calculated',
            $calculated ? $activity->provider_confirmed_at?->toIso8601String() : null,
            $calculated ? ($activity->awarded_points > 0 ? $activity->awarded_points.' point(s) by the configured rule.' : 'Not eligible: '.($activity->reward_note ?? 'no reward')) : null,
            $calculated && $activity->reward_status === RewardStatus::NotEligible ? 'skipped' : null,
        );
        $steps[] = $this->step(
            'credited',
            'Points credited',
            $activity->reward_credited_at?->toIso8601String(),
            null,
            $activity->reward_status === RewardStatus::NotEligible && $calculated ? 'skipped' : null,
        );

        return $steps;
    }

    /**
     * @return array{key: string, label: string, at: string|null, state: string, detail: string|null}
     */
    private function step(string $key, string $label, ?string $at, ?string $detail = null, ?string $state = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'at' => $at,
            'state' => $state ?? ($at !== null ? 'done' : 'pending'),
            'detail' => $detail,
        ];
    }
}
