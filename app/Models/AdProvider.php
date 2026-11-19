<?php

namespace App\Models;

use Database\Factories\AdProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @use HasFactory<AdProviderFactory>
 *
 * @property array{api_token?: string, api_secret?: string, reporting_access_token?: string}|null $settings
 * @property array<string, mixed>|null $config
 * @property Carbon|null $last_tested_at
 * @property Carbon|null $last_success_at
 */
#[Fillable(['key', 'label', 'is_enabled', 'priority', 'health', 'settings', 'config', 'last_tested_at', 'last_success_at', 'last_error_category'])]
#[Hidden(['settings'])]
class AdProvider extends Model
{
    /** @use HasFactory<AdProviderFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'settings' => 'encrypted:array',
            'config' => 'array',
            'last_tested_at' => 'datetime',
            'last_success_at' => 'datetime',
        ];
    }

    /**
     * Enabled providers in the order the fallback chain calls them: lowest priority number first.
     *
     * @param  Builder<AdProvider>  $query
     * @return Builder<AdProvider>
     */
    public function scopeInFallbackOrder(Builder $query): Builder
    {
        return $query->where('is_enabled', true)->orderBy('priority')->orderBy('id');
    }

    public function setting(string $name): ?string
    {
        $value = ($this->settings ?? [])[$name] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function configValue(string $name, mixed $default = null): mixed
    {
        return ($this->config ?? [])[$name] ?? $default;
    }

    /**
     * A masked fragment shown to administrators instead of the stored secret.
     */
    public function hint(string $name): ?string
    {
        $value = $this->setting($name);

        return $value === null ? null : '••••'.substr($value, -4);
    }
}
