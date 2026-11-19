<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'ai_provider_credential_id', 'action', 'error_category'])]
class AiCredentialAudit extends Model
{
    //
}
