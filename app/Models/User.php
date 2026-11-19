<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use App\Enums\UserRole;
use App\Exceptions\OtpThrottledException;
use App\Services\Otp\OtpService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $avatar_path
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property UserRole $role
 * @property Carbon|null $banned_at
 * @property string|null $ban_reason
 * @property Carbon|null $suspended_until
 * @property string|null $suspension_reason
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'email', 'password', 'avatar_path'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Email verification uses a 6-digit code, so the framework's "send verification" hook emails one.
     * A throttled or failed send must never break registration. The user can ask again on the verify page.
     */
    public function sendEmailVerificationNotification(): void
    {
        $otp = app(OtpService::class);

        try {
            $code = $otp->issue($this->email, OtpPurpose::EmailVerification, ip: request()->ip());
        } catch (OtpThrottledException) {
            return;
        }

        $otp->deliver($this->email, OtpPurpose::EmailVerification, $code);
    }

    /**
     * @var list<string>
     */
    protected $appends = [
        'avatar',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_confirmed_at' => 'datetime',
            'banned_at' => 'datetime',
            'suspended_until' => 'datetime',
        ];
    }

    /**
     * Public avatar URL for Inertia / UI consumers.
     */
    public function getAvatarAttribute(): ?string
    {
        if ($this->avatar_path === null || $this->avatar_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    /**
     * Determine whether the account may administer the platform.
     */
    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_until !== null && $this->suspended_until->isFuture();
    }

    public function isRestricted(): bool
    {
        return $this->isBanned() || $this->isSuspended();
    }

    /**
     * @return BelongsToMany<Quote, $this>
     */
    public function savedQuotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class, 'saved_quotes')->withTimestamps();
    }

    /**
     * @return HasOne<Wallet, $this>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
