<?php

namespace App\Models;

use App\Enums\CallbackResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property CallbackResult $result
 * @property Carbon $received_at
 * @property array<string, mixed>|null $payload
 */
#[Fillable(['provider_key', 'activity_id', 'provider_transaction_id', 'signature_valid', 'result', 'note', 'revenue_micros', 'payload', 'ip', 'received_at'])]
class ProviderCallback extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'result' => CallbackResult::class,
            'revenue_micros' => 'integer',
            'payload' => 'array',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AdActivity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(AdActivity::class, 'activity_id');
    }
}
