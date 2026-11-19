<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ai_provider_credential_id', 'ai_model_id', 'position'])]
class AiRouteStep extends Model
{
    public function credential(): BelongsTo
    {
        return $this->belongsTo(AiProviderCredential::class, 'ai_provider_credential_id')->withTrashed();
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }
}
