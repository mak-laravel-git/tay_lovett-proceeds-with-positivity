<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A one-time code sent by email. Only an HMAC of the code is stored, never the code itself.
 *
 * @property string $email
 * @property OtpPurpose $purpose
 * @property string $code_hash
 * @property string $salt
 * @property int $attempts
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 * @property Carbon $last_sent_at
 * @property array<string, mixed>|null $meta
 */
#[Fillable(['email', 'purpose', 'code_hash', 'salt', 'attempts', 'expires_at', 'consumed_at', 'last_sent_at', 'meta', 'ip'])]
#[Hidden(['code_hash', 'salt'])]
class OtpCode extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
