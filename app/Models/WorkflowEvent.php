<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One line of the activity log. Rows are written by WorkflowLogger and never edited.
 *
 * @property int $id
 * @property string|null $quote_workflow_id
 * @property int|null $user_id
 * @property string $category
 * @property string $event
 * @property string $level
 * @property array<string, mixed>|null $context
 * @property string|null $ip
 * @property Carbon|null $created_at
 */
#[Fillable(['quote_workflow_id', 'user_id', 'category', 'event', 'level', 'context', 'ip'])]
class WorkflowEvent extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
