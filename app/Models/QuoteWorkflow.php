<?php

namespace App\Models;

use App\Enums\WorkflowState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One run of the quote workflow for one user.
 *
 * @property string $id
 * @property int $user_id
 * @property int $quote_category_id
 * @property int|null $quote_id
 * @property WorkflowState $state
 * @property array<string, mixed> $settings_snapshot
 * @property string|null $ad1_result
 * @property string|null $survey_result
 * @property string|null $ad2_result
 * @property string|null $survey_activity_id
 * @property int|null $active_user_id
 * @property Carbon|null $expires_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id', 'quote_category_id', 'quote_id', 'state', 'settings_snapshot', 'ad1_result', 'survey_result',
    'ad2_result', 'survey_activity_id', 'active_user_id', 'expires_at', 'completed_at',
])]
class QuoteWorkflow extends Model
{
    use HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => WorkflowState::class,
            'settings_snapshot' => 'array',
            'quote_id' => 'integer',
            'active_user_id' => 'integer',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * One rule from the snapshot taken when the run started.
     */
    public function rule(string $name): mixed
    {
        return $this->settings_snapshot[$name] ?? (QuoteWorkflowSetting::defaults()[$name] ?? null);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<QuoteCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(QuoteCategory::class, 'quote_category_id');
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Surveys started from this run.
     *
     * @return HasMany<AdActivity, $this>
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(AdActivity::class, 'quote_workflow_id');
    }

    /**
     * @return HasMany<RewardedAdAttempt, $this>
     */
    public function adAttempts(): HasMany
    {
        return $this->hasMany(RewardedAdAttempt::class);
    }

    /**
     * @return HasMany<WorkflowEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(WorkflowEvent::class);
    }
}
