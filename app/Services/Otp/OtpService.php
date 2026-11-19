<?php

namespace App\Services\Otp;

use App\Enums\OtpPurpose;
use App\Enums\OtpStatus;
use App\Exceptions\OtpThrottledException;
use App\Models\OtpCode;
use App\Notifications\OtpCodeNotification;
use App\Services\AppSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

/**
 * Email one-time codes.
 *
 * Only an HMAC of the code is stored (keyed with a per-row salt and the app key), compared in constant time.
 * A code works once, expires, and is cancelled after too many wrong guesses. Issuing a new code replaces the
 * old row, and both a resend cooldown and an hourly cap apply per address.
 */
class OtpService
{
    public function __construct(private AppSettings $settings) {}

    public function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    /**
     * Create a new code, replacing any earlier one, and return it so the caller can send it.
     *
     * @param  array<string, mixed>  $meta
     *
     * @throws OtpThrottledException
     */
    public function issue(string $email, OtpPurpose $purpose, array $meta = [], ?string $ip = null): string
    {
        $email = $this->normalize($email);
        $this->guardThrottle($email, $purpose);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $salt = bin2hex(random_bytes(16));

        OtpCode::query()->updateOrCreate(
            ['email' => $email, 'purpose' => $purpose->value],
            [
                'code_hash' => $this->hash($code, $salt),
                'salt' => $salt,
                'attempts' => 0,
                'expires_at' => now()->addMinutes($this->expiryMinutes()),
                'consumed_at' => null,
                'last_sent_at' => now(),
                'meta' => $meta === [] ? null : $meta,
                'ip' => $ip,
            ],
        );

        RateLimiter::hit($this->hourKey($email, $purpose), 3600);

        return $code;
    }

    /**
     * Check a submitted code. A correct code is consumed so it cannot be used twice.
     */
    public function verify(string $email, OtpPurpose $purpose, string $code): OtpResult
    {
        $email = $this->normalize($email);
        $max = (int) $this->settings->get('authentication.otp_max_attempts');

        return DB::transaction(function () use ($email, $purpose, $code, $max): OtpResult {
            $row = OtpCode::query()
                ->where('email', $email)
                ->where('purpose', $purpose->value)
                ->lockForUpdate()
                ->first();

            if ($row === null || $row->consumed_at !== null) {
                return new OtpResult(OtpStatus::Missing);
            }

            if ($row->expires_at->isPast()) {
                return new OtpResult(OtpStatus::Expired);
            }

            if ($row->attempts >= $max) {
                return new OtpResult(OtpStatus::TooManyAttempts);
            }

            if (! hash_equals($row->code_hash, $this->hash(preg_replace('/\D/', '', $code) ?? '', $row->salt))) {
                $row->increment('attempts');

                return new OtpResult($row->attempts >= $max ? OtpStatus::TooManyAttempts : OtpStatus::Invalid);
            }

            $row->forceFill(['consumed_at' => now()])->save();

            return new OtpResult(OtpStatus::Verified, $row->meta ?? []);
        });
    }

    /**
     * Seconds the user must still wait before another code may be sent.
     */
    public function cooldownRemaining(string $email, OtpPurpose $purpose): int
    {
        $row = $this->active($email, $purpose);

        if ($row === null) {
            return 0;
        }

        $until = $row->last_sent_at->copy()->addSeconds((int) $this->settings->get('authentication.otp_resend_cooldown_seconds'));

        return max(0, (int) ceil(now()->diffInSeconds($until, false)));
    }

    /**
     * The live (unused, unexpired) code row for an address, if any.
     */
    public function active(string $email, OtpPurpose $purpose): ?OtpCode
    {
        return OtpCode::query()
            ->where('email', $this->normalize($email))
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function forget(string $email, OtpPurpose $purpose): void
    {
        OtpCode::query()
            ->where('email', $this->normalize($email))
            ->where('purpose', $purpose->value)
            ->delete();
    }

    public function expiryMinutes(): int
    {
        return (int) $this->settings->get('authentication.otp_expiry_minutes');
    }

    /**
     * Email the code. A failure is logged and reported as false, never thrown to the visitor.
     */
    public function deliver(string $email, OtpPurpose $purpose, string $code): bool
    {
        try {
            Notification::route('mail', $this->normalize($email))
                ->notify(new OtpCodeNotification($code, $purpose, $this->expiryMinutes()));

            return true;
        } catch (Throwable $exception) {
            Log::error('Could not send a verification code email.', [
                'purpose' => $purpose->value,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Remove codes that can no longer be used.
     */
    public function prune(): int
    {
        return OtpCode::query()
            ->where(fn ($query) => $query
                ->where('expires_at', '<', now()->subDay())
                ->orWhere('consumed_at', '<', now()->subDay()))
            ->delete();
    }

    /**
     * j***@example.com
     */
    public function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::substr($name, 0, 1).str_repeat('*', max(2, min(6, Str::length($name) - 1))).'@'.$domain;
    }

    /**
     * @throws OtpThrottledException
     */
    private function guardThrottle(string $email, OtpPurpose $purpose): void
    {
        $remaining = $this->cooldownRemaining($email, $purpose);

        if ($remaining > 0) {
            throw new OtpThrottledException($remaining);
        }

        $key = $this->hourKey($email, $purpose);
        $max = (int) $this->settings->get('authentication.otp_max_sends_per_hour');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw new OtpThrottledException(
                RateLimiter::availableIn($key),
                'Too many codes were requested. Please try again later.',
            );
        }
    }

    private function hourKey(string $email, OtpPurpose $purpose): string
    {
        return 'otp-hour:'.$purpose->value.':'.sha1($email);
    }

    private function hash(string $code, string $salt): string
    {
        return hash_hmac('sha256', $code, $salt.config('app.key'));
    }
}
