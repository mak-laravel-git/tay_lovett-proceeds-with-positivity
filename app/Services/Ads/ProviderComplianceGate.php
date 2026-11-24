<?php

namespace App\Services\Ads;

use App\Models\AdProvider;

/**
 * Hard policy gates that sit above "credentials present + enabled".
 * An administrator cannot make TheoremReach go live beside competing networks
 * without recording concurrent-provider permission.
 */
class ProviderComplianceGate
{
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_DISABLED = 'DISABLED';

    public const STATUS_MISSING_CREDENTIALS = 'MISSING_CREDENTIALS';

    public const STATUS_COMPLIANCE_BLOCKED = 'COMPLIANCE_BLOCKED';

    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';

    public const STATUS_SANDBOX = 'SANDBOX';

    /**
     * User-visible launch eligibility for a rewarded provider.
     *
     * @return array{status: string, label: string, can_launch: bool, reason: string|null}
     */
    public function launchState(AdProvider $provider, bool $configured): array
    {
        if (! $configured) {
            return $this->state(self::STATUS_MISSING_CREDENTIALS, 'Setup in progress', false, 'missing_credentials');
        }

        if (! $provider->is_enabled) {
            return $this->state(self::STATUS_DISABLED, 'Temporarily unavailable', false, 'disabled');
        }

        $approval = (string) $provider->configValue('approval_status', 'unverified');

        if (in_array($approval, ['pending', 'pending_approval'], true)) {
            return $this->state(self::STATUS_PENDING_APPROVAL, 'Awaiting provider activation', false, 'pending_approval');
        }

        if ($provider->key === 'theoremreach' && ! $this->allowsConcurrentProviders($provider)) {
            return $this->state(
                self::STATUS_COMPLIANCE_BLOCKED,
                'Not yet available',
                false,
                'Pending provider coexistence authorization',
            );
        }

        if ($provider->configValue('sandbox_mode', false) === true) {
            return $this->state(self::STATUS_SANDBOX, 'Test mode — no real earnings', true, 'sandbox');
        }

        if ($provider->configValue('production_enabled', false) !== true && ! app()->environment('local', 'testing')) {
            return $this->state(self::STATUS_PENDING_APPROVAL, 'Awaiting provider activation', false, 'production_not_enabled');
        }

        return $this->state(self::STATUS_ACTIVE, 'Available', true, null);
    }

    public function allowsConcurrentProviders(AdProvider $provider): bool
    {
        // Do not auto-bypass in testing: fixtures must set concurrent_provider_permission or sandbox_mode.
        return $provider->configValue('concurrent_provider_permission', false) === true
            || $provider->configValue('sandbox_mode', false) === true;
    }

    public function allowsCashout(AdProvider $provider): bool
    {
        return $provider->configValue('reward_cashout_permission', false) === true;
    }

    /**
     * @param  list<string>|null  $allowed
     */
    public function ipAllowed(AdProvider $provider, ?string $ip): bool
    {
        /** @var list<mixed>|null $configured */
        $configured = $provider->configValue('allowed_callback_ips');

        if (! is_array($configured) || $configured === []) {
            return true;
        }

        if ($ip === null || $ip === '') {
            return false;
        }

        $allowed = array_values(array_filter($configured, fn (mixed $value): bool => is_string($value) && $value !== ''));

        if ($allowed === []) {
            return true;
        }

        // Local/fixture tests and private networks skip the vendor allowlist unless forced.
        if (app()->environment('local', 'testing') && $provider->configValue('enforce_callback_ips_in_local', false) !== true) {
            return true;
        }

        return in_array($ip, $allowed, true);
    }

    /**
     * @return array{status: string, label: string, can_launch: bool, reason: string|null}
     */
    private function state(string $status, string $label, bool $canLaunch, ?string $reason): array
    {
        return [
            'status' => $status,
            'label' => $label,
            'can_launch' => $canLaunch,
            'reason' => $reason,
        ];
    }
}
