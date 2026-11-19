<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'activity_id', 'entry_type', 'points', 'idempotency_key', 'description'])]
class PointLedgerEntry extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_REWARD = 'reward';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AdActivity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(AdActivity::class, 'activity_id');
    }
}
