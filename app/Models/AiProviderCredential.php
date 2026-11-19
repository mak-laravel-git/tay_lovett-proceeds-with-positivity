<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property array{key?: string, url?: string} $payload
 * @property Carbon|null $last_tested_at
 */
#[Fillable(['driver', 'label', 'priority', 'is_enabled', 'health', 'payload', 'last_tested_at', 'last_error_category'])]
#[Hidden(['payload'])]
class AiProviderCredential extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'payload' => 'encrypted:array',
            'last_tested_at' => 'datetime',
        ];
    }
}
