<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ai_budget_counter_id', 'idempotency_key', 'amount_cents', 'state'])]
class AiBudgetReservation extends Model
{
    /**
     * @return BelongsTo<AiBudgetCounter, $this>
     */
    public function counter(): BelongsTo
    {
        return $this->belongsTo(AiBudgetCounter::class, 'ai_budget_counter_id');
    }
}
