<?php

namespace App\Support;

use Closure;
use InvalidArgumentException;

/**
 * The single source of truth for every application setting: its type, where the fallback comes from
 * (.env / config) and how it is validated. The service, controller and reset all read from here.
 */
class AppSettingsRegistry
{
    /**
     * .env/config values captured before any override is applied to config().
     *
     * @var array<string, mixed>|null
     */
    private static ?array $baseline = null;

    public const TYPE_STRING = 'string';

    public const TYPE_INT = 'int';

    public const TYPE_BOOL = 'bool';

    /**
     * @param  array<string, mixed>  $baseline
     */
    public static function setBaseline(array $baseline): void
    {
        self::$baseline = $baseline;
    }

    /**
     * Read a config value as it was before runtime overrides. Falls back to live config when no snapshot exists.
     */
    public static function base(string $key, mixed $default = null): mixed
    {
        if (self::$baseline === null) {
            return config($key, $default);
        }

        foreach (self::$baseline as $root => $value) {
            if ($key === $root) {
                return $value ?? $default;
            }

            if (str_starts_with($key, $root.'.')) {
                return data_get($value, substr($key, strlen($root) + 1), $default);
            }
        }

        return config($key, $default);
    }

    /**
     * @return array<string, array<string, array{type: string, default: Closure|string|int|bool|null, secret?: bool}>>
     */
    public static function groups(): array
    {
        return [
            'general' => [
                'site_name' => ['type' => self::TYPE_STRING, 'default' => fn () => self::base('app.name')],
                'tagline' => ['type' => self::TYPE_STRING, 'default' => 'GROW · INSPIRE · EARN'],
                'meta_description' => ['type' => self::TYPE_STRING, 'default' => ''],
                'footer_text' => ['type' => self::TYPE_STRING, 'default' => ''],
                'logo_path' => ['type' => self::TYPE_STRING, 'default' => null],
                'favicon_path' => ['type' => self::TYPE_STRING, 'default' => null],
            ],
            'smtp' => [
                'enabled' => ['type' => self::TYPE_BOOL, 'default' => fn () => self::base('mail.default') === 'smtp'],
                'host' => ['type' => self::TYPE_STRING, 'default' => fn () => (string) self::base('mail.mailers.smtp.host')],
                'port' => ['type' => self::TYPE_INT, 'default' => fn () => (int) self::base('mail.mailers.smtp.port')],
                'encryption' => ['type' => self::TYPE_STRING, 'default' => fn () => self::envEncryption()],
                'username' => ['type' => self::TYPE_STRING, 'default' => fn () => (string) self::base('mail.mailers.smtp.username')],
                'password' => ['type' => self::TYPE_STRING, 'default' => fn () => (string) self::base('mail.mailers.smtp.password'), 'secret' => true],
                'from_address' => ['type' => self::TYPE_STRING, 'default' => fn () => (string) self::base('mail.from.address')],
                'from_name' => ['type' => self::TYPE_STRING, 'default' => fn () => (string) self::base('mail.from.name')],
                'timeout' => ['type' => self::TYPE_INT, 'default' => 30],
            ],
            'authentication' => [
                'registration_open' => ['type' => self::TYPE_BOOL, 'default' => true],
                'otp_expiry_minutes' => ['type' => self::TYPE_INT, 'default' => 10],
                'otp_resend_cooldown_seconds' => ['type' => self::TYPE_INT, 'default' => 60],
                'otp_max_attempts' => ['type' => self::TYPE_INT, 'default' => 5],
                'otp_max_sends_per_hour' => ['type' => self::TYPE_INT, 'default' => 5],
                'confirm_timeout_minutes' => ['type' => self::TYPE_INT, 'default' => fn () => max(1, (int) ceil(((int) self::base('auth.password_timeout', 10800)) / 60))],
            ],
            'contact' => [
                'support_email' => ['type' => self::TYPE_STRING, 'default' => ''],
                'phone' => ['type' => self::TYPE_STRING, 'default' => ''],
                'address' => ['type' => self::TYPE_STRING, 'default' => ''],
                'facebook_url' => ['type' => self::TYPE_STRING, 'default' => ''],
                'x_url' => ['type' => self::TYPE_STRING, 'default' => ''],
                'instagram_url' => ['type' => self::TYPE_STRING, 'default' => ''],
                'youtube_url' => ['type' => self::TYPE_STRING, 'default' => ''],
            ],
            // The on/off switch itself is real Laravel maintenance mode (php artisan down/up), driven by
            // MaintenanceController. These three fields only remember the last text an administrator typed,
            // so the form is prefilled next time, and they are baked into the prerendered page when it goes down.
            'maintenance' => [
                'title' => ['type' => self::TYPE_STRING, 'default' => 'We will be right back'],
                'message' => ['type' => self::TYPE_STRING, 'default' => 'The site is down for a short update. Please check back soon.'],
                'expected_back_at' => ['type' => self::TYPE_STRING, 'default' => ''],
            ],
        ];
    }

    /**
     * @return array{type: string, default: Closure|string|int|bool|null, secret?: bool}
     */
    public static function field(string $key): array
    {
        [$group, $name] = array_pad(explode('.', $key, 2), 2, '');

        return self::groups()[$group][$name]
            ?? throw new InvalidArgumentException("Unknown application setting [{$key}].");
    }

    /**
     * @return list<string>
     */
    public static function groupNames(): array
    {
        return array_keys(self::groups());
    }

    /**
     * @return list<string> Field names (without the group prefix).
     */
    public static function fieldNames(string $group): array
    {
        return array_keys(self::groups()[$group] ?? []);
    }

    public static function exists(string $group): bool
    {
        return array_key_exists($group, self::groups());
    }

    public static function default(string $key): mixed
    {
        $default = self::field($key)['default'];

        return $default instanceof Closure ? $default() : $default;
    }

    private static function envEncryption(): string
    {
        $scheme = self::base('mail.mailers.smtp.scheme');
        $port = (int) self::base('mail.mailers.smtp.port');

        return match (true) {
            $scheme === 'smtps', $port === 465 => 'ssl',
            $port === 587 => 'tls',
            default => 'none',
        };
    }
}
