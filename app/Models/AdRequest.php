<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'activity_type', 'resolved_provider_key', 'outcome', 'attempt_count', 'offer_count'])]
class AdRequest extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AdProviderCall, $this>
     */
    public function calls(): HasMany
    {
        return $this->hasMany(AdProviderCall::class)->orderBy('position');
    }
}
