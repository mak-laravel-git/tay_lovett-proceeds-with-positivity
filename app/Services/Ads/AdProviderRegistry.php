<?php

namespace App\Services\Ads;

use App\Models\AdProvider;
use App\Services\Ads\Contracts\AdProviderAdapter;

/**
 * Every provider adapter known to the code. Adding a provider means writing an adapter and registering it in
 * AdsServiceProvider; the database row (credentials, priority, enabled flag) is created automatically.
 */
class AdProviderRegistry
{
    /** @var array<string, AdProviderAdapter> */
    private array $adapters = [];

    public function register(AdProviderAdapter $adapter): void
    {
        $this->adapters[$adapter->key()] = $adapter;
    }

    public function has(string $key): bool
    {
        return isset($this->adapters[$key]);
    }

    public function get(string $key): ?AdProviderAdapter
    {
        return $this->adapters[$key] ?? null;
    }

    /**
     * @return array<string, AdProviderAdapter>
     */
    public function all(): array
    {
        return $this->adapters;
    }

    /**
     * Make sure each registered adapter has a database row. New rows start disabled and last in priority, so
     * nothing goes live until an administrator adds credentials and enables it.
     */
    public function ensureRows(): void
    {
        foreach ($this->adapters as $key => $adapter) {
            if (AdProvider::withTrashed()->where('key', $key)->exists()) {
                continue;
            }

            AdProvider::query()->create([
                'key' => $key,
                'label' => $adapter->label(),
                'is_enabled' => false,
                'priority' => ((int) AdProvider::withTrashed()->max('priority')) + 10,
                'health' => 'unknown',
                'config' => $this->defaultConfig($key),
            ]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function defaultConfig(string $key): ?array
    {
        return match ($key) {
            'wannads' => [
                'approval_status' => 'unverified',
                'production_enabled' => false,
                'reward_cashout_permission' => false,
                'concurrent_provider_permission' => false,
                'sandbox_mode' => false,
                'allowed_callback_ips' => ['3.22.177.178'],
            ],
            'theoremreach' => [
                'approval_status' => 'unverified',
                'production_enabled' => false,
                'reward_cashout_permission' => false,
                'concurrent_provider_permission' => false,
                'sandbox_mode' => false,
                'partner_id' => null,
                'currency_name_plural' => 'Points',
                'exchange_rate' => null,
            ],
            'google_adsense' => [
                'integration_type' => 'standard_ads',
                'production_enabled' => false,
            ],
            default => null,
        };
    }
}
