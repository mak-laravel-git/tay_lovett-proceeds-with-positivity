<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['driver', 'name', 'is_enabled', 'structured_output_tested', 'max_request_cents'])]
class AiModel extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'structured_output_tested' => 'boolean',
        ];
    }
}
