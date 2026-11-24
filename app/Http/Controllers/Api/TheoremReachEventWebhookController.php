<?php

namespace App\Http\Controllers\Api;

use App\Enums\ActivityStatus;
use App\Enums\CallbackResult;
use App\Enums\RewardStatus;
use App\Http\Controllers\Controller;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\ProviderCallback;
use App\Services\Ads\Providers\TheoremReach\TheoremReachAdapter;
use App\Services\Ads\Providers\TheoremReach\TheoremReachSignature;
use App\Services\Ads\RewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * TheoremReach Event Webhook for reversals and bans.
 *
 * Official FAQ: reversals are NOT sent through the standard Reward Callback.
 * The webhook URL carries enc_salt + hash (HMAC-SHA1 of the URL up to &hash=).
 * Any 2xx acknowledges; otherwise TheoremReach retries for about a week.
 */
class TheoremReachEventWebhookController extends Controller
{
    public function __invoke(Request $request, RewardService $rewards): Response
    {
        $provider = AdProvider::query()->where('key', TheoremReachAdapter::KEY)->first();

        if ($provider === null || $provider->setting('api_secret') === null) {
            return response()->json(['status' => 'rejected'], 404);
        }

        $secret = (string) $provider->setting('api_secret');
        $valid = TheoremReachSignature::verifyCallbackUrl($request->fullUrl(), $secret)
            || TheoremReachSignature::verifyCallbackQuery($request->server->getString('QUERY_STRING'), $secret);

        if (! $valid) {
            ProviderCallback::query()->create([
                'provider_key' => TheoremReachAdapter::KEY,
                'signature_valid' => false,
                'result' => CallbackResult::InvalidSignature,
                'note' => 'event_webhook_signature_mismatch',
                'revenue_micros' => 0,
                'payload' => ['query' => $request->query(), 'body_keys' => array_keys($request->all())],
                'ip' => $request->ip(),
                'received_at' => now(),
            ]);

            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $payload = $request->isJson() ? $request->json()->all() : $request->all();
        $eventType = strtolower((string) ($payload['event_type'] ?? $request->query('event_type', '')));

        $reversed = [];
        if (isset($payload['reversed_transactions']) && is_array($payload['reversed_transactions'])) {
            $reversed = $payload['reversed_transactions'];
        } elseif (isset($payload['transaction_id'])) {
            $reversed = [$payload['transaction_id']];
        }

        $results = DB::transaction(function () use ($reversed, $eventType, $rewards, $request, $payload): array {
            $out = [];

            foreach ($reversed as $tx) {
                $txId = is_array($tx) ? (string) ($tx['transaction_id'] ?? $tx['tx_id'] ?? '') : (string) $tx;

                if ($txId === '') {
                    continue;
                }

                $activity = AdActivity::query()
                    ->where('provider_key', TheoremReachAdapter::KEY)
                    ->where('provider_transaction_id', $txId)
                    ->lockForUpdate()
                    ->first();

                if ($activity === null) {
                    $out[] = ['transaction_id' => $txId, 'result' => 'not_found'];

                    continue;
                }

                if ($activity->status === ActivityStatus::Reversed || $activity->reward_status === RewardStatus::Reversed) {
                    $out[] = ['transaction_id' => $txId, 'result' => 'already_reversed'];

                    continue;
                }

                if ($eventType === 'ban') {
                    $activity->forceFill([
                        'reward_note' => trim(($activity->reward_note ?? '').' provider_ban'),
                    ])->save();
                }

                $rewards->reverse($activity, 'theoremreach_event_'.$eventType);
                $out[] = ['transaction_id' => $txId, 'result' => 'reversed'];
            }

            ProviderCallback::query()->create([
                'provider_key' => TheoremReachAdapter::KEY,
                'signature_valid' => true,
                'result' => CallbackResult::Reversed,
                'note' => 'event_webhook:'.$eventType,
                'revenue_micros' => 0,
                'payload' => [
                    'event_type' => $eventType,
                    'reversed' => $out,
                    // Avoid storing large raw demographics; keep ids only.
                    'keys' => array_keys($payload),
                ],
                'ip' => $request->ip(),
                'received_at' => now(),
            ]);

            return $out;
        });

        return response()->json(['status' => 'ok', 'results' => $results], 200);
    }
}
