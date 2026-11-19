<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $meta
 */
#[Fillable(['user_id', 'ad_provider_id', 'action', 'meta'])]
class AdProviderAudit extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AdProvider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AdProvider::class, 'ad_provider_id');
    }
}
