<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Support\AppSettingsRegistry;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Runtime application settings.
 *
 * Resolution order for every value: request memo, shared cache, database override, then the .env/config
 * default captured before any override is applied. A missing table or an unreachable cache never breaks
 * the site, it simply falls back to .env.
 */
class AppSettings
{
    public const CACHE_KEY = 'app_settings:v1';

    /**
     * @var array<string, array{v: string|null, s: bool}>|null
     */
    private ?array $rows = null;

    public function __construct()
    {
        $this->captureBaseline();
    }

    /**
     * Remember the .env/config values before overrides touch config(). Defaults read from this snapshot.
     */
    public function captureBaseline(): void
    {
        AppSettingsRegistry::setBaseline([
            'app.name' => config('app.name'),
            'mail.default' => config('mail.default'),
            'mail.from' => config('mail.from'),
            'mail.mailers.smtp' => config('mail.mailers.smtp'),
            'auth.password_timeout' => config('auth.password_timeout'),
        ]);
    }

    public function get(string $key): mixed
    {
        $field = AppSettingsRegistry::field($key);
        $row = $this->rows()[$key] ?? null;

        if ($row === null) {
            return $this->cast($field['type'], AppSettingsRegistry::default($key));
        }

        $raw = $row['v'];

        try {
            if ($raw !== null && $row['s']) {
                $raw = Crypt::decryptString($raw);
            }
        } catch (DecryptException) {
            return $this->cast($field['type'], AppSettingsRegistry::default($key));
        }

        return $this->cast($field['type'], $raw === null ? null : json_decode($raw, true));
    }

    /**
     * Every resolved value of a group, secrets included. Never send this to the browser.
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $values = [];

        foreach (AppSettingsRegistry::fieldNames($group) as $name) {
            $values[$name] = $this->get($group.'.'.$name);
        }

        return $values;
    }

    /**
     * Browser-safe description of a group: value, where it came from, and a flag instead of any secret.
     *
     * @return array<string, array{value: mixed, source: string, secret: bool, has_value: bool}>
     */
    public function describe(string $group): array
    {
        $fields = [];

        foreach (AppSettingsRegistry::groups()[$group] as $name => $definition) {
            $key = $group.'.'.$name;
            $secret = $definition['secret'] ?? false;
            $value = $this->get($key);

            $fields[$name] = [
                'value' => $secret ? null : $value,
                'source' => $this->source($key),
                'secret' => $secret,
                'has_value' => filled($value),
            ];
        }

        return $fields;
    }

    public function isOverridden(string $key): bool
    {
        return isset($this->rows()[$key]);
    }

    /**
     * @return 'database'|'env'
     */
    public function source(string $key): string
    {
        return $this->isOverridden($key) ? 'database' : 'env';
    }

    public function set(string $key, mixed $value, ?int $userId = null): void
    {
        $this->setMany([$key => $value], $userId);
    }

    /**
     * Store several overrides in one transaction. Keys are fully qualified (group.name).
     *
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values, ?int $userId = null): void
    {
        DB::transaction(function () use ($values, $userId): void {
            foreach ($values as $key => $value) {
                $field = AppSettingsRegistry::field($key);
                $secret = $field['secret'] ?? false;
                $encoded = json_encode($this->cast($field['type'], $value));

                AppSetting::query()->updateOrCreate(['key' => $key], [
                    'group' => explode('.', $key, 2)[0],
                    'value' => $secret ? Crypt::encryptString($encoded) : $encoded,
                    'is_secret' => $secret,
                    'updated_by' => $userId,
                ]);
            }
        });

        $this->refresh();
    }

    /**
     * Drop one override so the .env/config value applies again.
     */
    public function forget(string $key): void
    {
        AppSetting::query()->where('key', $key)->delete();
        $this->refresh();
    }

    /**
     * Drop every override of a group.
     */
    public function reset(string $group): void
    {
        AppSetting::query()->where('group', $group)->delete();
        $this->refresh();
    }

    /**
     * Forget the cache and request memo, then re-apply runtime configuration.
     */
    public function refresh(): void
    {
        $this->rows = null;

        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable $exception) {
            Log::warning('Could not clear the application settings cache.', ['error' => $exception->getMessage()]);
        }

        $this->applyToConfig();
    }

    /**
     * Overlay the resolved settings on top of Laravel's config so mail, the app name and the sensitive-area
     * timeout follow the database without every caller knowing about it.
     */
    public function applyToConfig(): void
    {
        config([
            'app.name' => $this->get('general.site_name'),
            'mail.from.address' => $this->get('smtp.from_address'),
            'mail.from.name' => $this->get('smtp.from_name'),
            'auth.password_timeout' => ((int) $this->get('authentication.confirm_timeout_minutes')) * 60,
        ]);

        if ($this->get('smtp.enabled') && filled($this->get('smtp.host'))) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp' => array_merge(
                    (array) config('mail.mailers.smtp'),
                    $this->smtpTransportConfig($this->group('smtp')),
                ),
            ]);
        } else {
            config([
                'mail.default' => AppSettingsRegistry::base('mail.default'),
                'mail.mailers.smtp' => AppSettingsRegistry::base('mail.mailers.smtp'),
            ]);
        }

        if (app()->resolved('mail.manager')) {
            Mail::purge('smtp');
        }
    }

    /**
     * Translate the admin-friendly SMTP values into a Laravel mailer config.
     *
     * @param  array<string, mixed>  $smtp
     * @return array<string, mixed>
     */
    public function smtpTransportConfig(array $smtp): array
    {
        $encryption = (string) ($smtp['encryption'] ?? 'none');

        return array_filter([
            'transport' => 'smtp',
            'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => $smtp['host'] ?? null,
            'port' => $smtp['port'] ?? null,
            'username' => filled($smtp['username'] ?? null) ? $smtp['username'] : null,
            'password' => filled($smtp['password'] ?? null) ? $smtp['password'] : null,
            'timeout' => $smtp['timeout'] ?? null,
            'auto_tls' => $encryption === 'none' ? false : null,
            'require_tls' => $encryption === 'tls' ? true : null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * The real Laravel maintenance mode state, plus the text an administrator last used, for the admin UI.
     *
     * @return array{active: bool, bypassUrl: string|null, retryAfter: string|null, title: string, message: string, expectedBackAt: string}
     */
    public function maintenanceStatus(): array
    {
        $active = app()->maintenanceMode()->active();
        $data = $active ? app()->maintenanceMode()->data() : [];
        $secret = $data['secret'] ?? null;

        return [
            'active' => $active,
            'bypassUrl' => is_string($secret) && $secret !== '' ? url('/'.$secret) : null,
            'retryAfter' => is_string($data['retry'] ?? null) ? $data['retry'] : null,
            'title' => (string) $this->get('maintenance.title'),
            'message' => (string) $this->get('maintenance.message'),
            'expectedBackAt' => (string) $this->get('maintenance.expected_back_at'),
        ];
    }

    public function logoUrl(): ?string
    {
        return $this->publicUrl($this->get('general.logo_path'));
    }

    public function faviconUrl(): ?string
    {
        return $this->publicUrl($this->get('general.favicon_path'));
    }

    /**
     * Public, non-secret values every page needs. Cheap: it reads the cached rows only.
     *
     * @return array<string, mixed>
     */
    public function site(): array
    {
        $contact = $this->group('contact');

        return [
            'name' => (string) $this->get('general.site_name'),
            'tagline' => (string) $this->get('general.tagline'),
            'description' => (string) $this->get('general.meta_description'),
            'footer_text' => (string) $this->get('general.footer_text'),
            'logo_url' => $this->logoUrl(),
            'favicon_url' => $this->faviconUrl(),
            'registration_open' => (bool) $this->get('authentication.registration_open'),
            'contact' => [
                'support_email' => $contact['support_email'],
                'phone' => $contact['phone'],
                'address' => $contact['address'],
            ],
            'social' => array_filter([
                'facebook' => $contact['facebook_url'],
                'x' => $contact['x_url'],
                'instagram' => $contact['instagram_url'],
                'youtube' => $contact['youtube_url'],
            ], filled(...)),
        ];
    }

    /**
     * @return array<string, array{v: string|null, s: bool}>
     */
    private function rows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        try {
            $this->rows = Cache::rememberForever(self::CACHE_KEY, fn (): array => AppSetting::query()
                ->get(['key', 'value', 'is_secret'])
                ->mapWithKeys(fn (AppSetting $row): array => [$row->key => ['v' => $row->value, 's' => (bool) $row->is_secret]])
                ->all());
        } catch (Throwable $exception) {
            Log::warning('Application settings are unavailable, using .env values.', ['error' => $exception->getMessage()]);
            $this->rows = [];
        }

        return $this->rows;
    }

    private function cast(string $type, mixed $value): mixed
    {
        return match ($type) {
            AppSettingsRegistry::TYPE_INT => $value === null || $value === '' ? 0 : (int) $value,
            AppSettingsRegistry::TYPE_BOOL => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value === null ? null : (string) $value,
        };
    }

    private function publicUrl(mixed $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url((string) $path) : null;
    }
}
