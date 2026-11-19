<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One stored override. Rows only exist for values an administrator saved; everything else falls back to .env/config.
 *
 * @property string $group
 * @property string $key
 * @property string|null $value JSON encoded. Encrypted first when is_secret is true.
 * @property bool $is_secret
 */
#[Fillable(['group', 'key', 'value', 'is_secret', 'updated_by'])]
class AppSetting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
        ];
    }
}
