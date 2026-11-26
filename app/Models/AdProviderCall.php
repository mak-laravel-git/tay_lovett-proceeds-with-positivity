<?php

namespace App\Models;

use App\Enums\CallOutcome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CallOutcome $outcome
 * @property string|null $response_excerpt
 */
#[Fillable(['ad_request_id', 'provider_key', 'operation', 'outcome', 'http_status', 'latency_ms', 'error_category', 'response_excerpt', 'position'])]
class AdProviderCall extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['outcome' => CallOutcome::class];
    }

    /**
     * @return BelongsTo<AdRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(AdRequest::class, 'ad_request_id');
    }
}
