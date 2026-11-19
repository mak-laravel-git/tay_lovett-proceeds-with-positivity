<?php

namespace App\Services\Ads;

use App\Models\ProviderIdentity;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * The opaque identifier a provider knows each user by. It is random, stable, and never derived from the
 * user's email or name, so nothing personal is sent to a third party.
 */
class ProviderIdentities
{
    public function externalIdFor(User $user, string $providerKey): string
    {
        $existing = ProviderIdentity::query()
            ->where('provider_key', $providerKey)
            ->where('user_id', $user->id)
            ->value('external_user_id');

        if (is_string($existing)) {
            return $existing;
        }

        try {
            return ProviderIdentity::query()->create([
                'user_id' => $user->id,
                'provider_key' => $providerKey,
                'external_user_id' => 'u'.Str::lower(Str::random(23)),
            ])->external_user_id;
        } catch (UniqueConstraintViolationException) {
            // Created concurrently by another request: use the winner.
            return (string) ProviderIdentity::query()
                ->where('provider_key', $providerKey)
                ->where('user_id', $user->id)
                ->value('external_user_id');
        }
    }

    public function userIdFor(string $providerKey, string $externalUserId): ?int
    {
        $id = ProviderIdentity::query()
            ->where('provider_key', $providerKey)
            ->where('external_user_id', $externalUserId)
            ->value('user_id');

        return $id === null ? null : (int) $id;
    }
}
