<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\GatePurpose;
use App\Enums\RewardStatus;
use Database\Factories\AdActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One independent transaction record per survey attempt (or per unmatched provider payout).
 *
 * @property string $id
 * @property ActivityStatus $status
 * @property RewardStatus $reward_status
 * @property ActivityType $activity_type
 * @property Carbon|null $survey_loaded_at
 * @property Carbon|null $survey_started_at
 * @property Carbon|null $survey_returned_at
 * @property Carbon|null $provider_confirmed_at
 * @property Carbon|null $reward_credited_at
 * @property GatePurpose|null $purpose
 * @property string|null $quote_workflow_id
 * @property Carbon|null $consumed_at
 * @property int|null $consumed_quote_id
 * @property array<string, mixed>|null $callback_payload
 * @property int $publisher_revenue_micros
 * @property int $awarded_points
 */
#[Fillable([
    'user_id', 'ad_request_id', 'source', 'provider_key', 'provider_user_id', 'activity_type', 'purpose', 'quote_workflow_id',
    'consumed_at', 'consumed_quote_id', 'offer_identifier', 'offer_length_minutes', 'offer_category', 'provider_transaction_id',
    'status', 'reward_status', 'reward_note',
    'survey_loaded_at', 'survey_started_at', 'survey_returned_at', 'provider_confirmed_at', 'reward_credited_at',
    'provider_status', 'survey_outcome', 'payout_type', 'provider_payout_amount', 'payout_currency',
    'publisher_revenue_micros', 'currency', 'awarded_points', 'callback_verification_status', 'callback_payload',
    'country', 'error_code', 'error_message',
])]
class AdActivity extends Model
{
    /** @use HasFactory<AdActivityFactory> */
    use HasFactory, HasUlids;

    public const SOURCE_APP = 'app';

    public const SOURCE_POSTBACK_ONLY = 'postback_only';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ActivityStatus::class,
            'reward_status' => RewardStatus::class,
            'activity_type' => ActivityType::class,
            'purpose' => GatePurpose::class,
            'consumed_at' => 'datetime',
            'survey_loaded_at' => 'datetime',
            'survey_started_at' => 'datetime',
            'survey_returned_at' => 'datetime',
            'provider_confirmed_at' => 'datetime',
            'reward_credited_at' => 'datetime',
            'callback_payload' => 'array',
            'publisher_revenue_micros' => 'integer',
            'provider_payout_amount' => 'integer',
            'awarded_points' => 'integer',
            'survey_outcome' => 'integer',
            'payout_type' => 'integer',
        ];
    }

    /**
     * Whether a completion may earn points or a revenue share. Surveys done to unlock a quote only do when the
     * rules of that run say so; every other survey always may.
     */
    public function earnsRewards(): bool
    {
        if ($this->quote_workflow_id === null) {
            return true;
        }

        return QuoteWorkflow::query()->find($this->quote_workflow_id)?->rule('award_survey_points') === true;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AdRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(AdRequest::class, 'ad_request_id');
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function consumedQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'consumed_quote_id');
    }

    /**
     * @return HasMany<ProviderCallback, $this>
     */
    public function callbacks(): HasMany
    {
        return $this->hasMany(ProviderCallback::class, 'activity_id')->orderBy('received_at');
    }

    /**
     * @return HasMany<PointLedgerEntry, $this>
     */
    public function pointEntries(): HasMany
    {
        return $this->hasMany(PointLedgerEntry::class, 'activity_id');
    }
}
