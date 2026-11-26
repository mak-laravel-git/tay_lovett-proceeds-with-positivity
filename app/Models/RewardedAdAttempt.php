<?php

namespace App\Models;

use App\Enums\AdAttemptStatus;
use App\Enums\AdStep;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One attempt to show one rewarded ad inside a workflow run.
 *
 * @property string $id
 * @property string $quote_workflow_id
 * @property int $user_id
 * @property AdStep $step
 * @property int|null $ad_placement_id
 * @property string|null $ad_unit_path
 * @property AdAttemptStatus $status
 * @property int $min_watch_seconds
 * @property Carbon|null $ready_at
 * @property Carbon|null $shown_at
 * @property Carbon|null $granted_at
 * @property Carbon|null $closed_at
 * @property string|null $error_detail
 * @property Carbon|null $created_at
 */
#[Fillable([
    'quote_workflow_id', 'user_id', 'step', 'ad_placement_id', 'ad_unit_path', 'status', 'min_watch_seconds',
    'ready_at', 'shown_at', 'granted_at', 'closed_at', 'error_detail', 'ip', 'user_agent',
])]
class RewardedAdAttempt extends Model
{
    use HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step' => AdStep::class,
            'status' => AdAttemptStatus::class,
            'min_watch_seconds' => 'integer',
            'ready_at' => 'datetime',
            'shown_at' => 'datetime',
            'granted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<QuoteWorkflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(QuoteWorkflow::class, 'quote_workflow_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AdPlacement, $this>
     */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(AdPlacement::class, 'ad_placement_id');
    }
}
