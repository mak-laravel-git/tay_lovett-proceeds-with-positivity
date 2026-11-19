<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Settled / paid publisher revenue, recorded manually: no provider API reports it.
 *
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property Carbon $received_at
 */
#[Fillable(['provider_key', 'period_start', 'period_end', 'amount_micros', 'reference', 'received_at', 'recorded_by'])]
class AdProviderPayout extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'received_at' => 'date',
            'amount_micros' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
