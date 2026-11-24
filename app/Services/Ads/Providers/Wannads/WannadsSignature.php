<?php

namespace App\Services\Ads\Providers\Wannads;

/**
 * Wannads Reward App Postback signature.
 *
 * Official documented form (provider-mandated legacy MD5, not a general password hash):
 *   md5(user_id + transaction_id + reward + SECRET)
 *
 * Use the app SECRET, never the API SECRET. Compare the reward string exactly as received.
 */
final class WannadsSignature
{
    public static function sign(string $userId, string $transactionId, string $reward, string $secret): string
    {
        return md5($userId.$transactionId.$reward.$secret);
    }

    public static function verify(string $userId, string $transactionId, string $reward, string $secret, string $provided): bool
    {
        if ($provided === '' || $secret === '') {
            return false;
        }

        return hash_equals(strtolower(self::sign($userId, $transactionId, $reward, $secret)), strtolower($provided));
    }
}
