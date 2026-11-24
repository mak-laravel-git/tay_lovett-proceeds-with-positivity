<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\GatePurpose;
use App\Enums\RewardStatus;
use App\Models\AdActivity;
use App\Models\AdProvider;
use App\Models\AdRequest;
use App\Services\Ads\AdProviderManager;
use App\Services\Ads\AdProviderRegistry;
use App\Services\Ads\Contracts\HandlesReturn;
use App\Services\Ads\Data\OfferRequest;
use App\Services\Ads\PointLedger;
use App\Services\Ads\ProviderComplianceGate;
use App\Services\Ads\ProviderIdentities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The user-facing survey / offer flow. Rewarded providers are listed as cards; offers open in
 * an in-app frame or new tab. This controller never approves a survey or awards points — only the
 * verified provider callback does.
 */
class SurveyController extends Controller
{
    public function index(Request $request, PointLedger $points, AdProviderRegistry $registry, ProviderComplianceGate $compliance): Response
    {
        $registry->ensureRows();

        $rewarded = ['tapresearch', 'wannads', 'theoremreach'];

        $providers = AdProvider::query()
            ->whereIn('key', $rewarded)
            ->orderBy('priority')
            ->get()
            ->map(function (AdProvider $provider) use ($registry, $compliance): array {
                $adapter = $registry->get($provider->key);
                $configured = $adapter !== null && $adapter->isConfigured($provider);
                $state = $compliance->launchState($provider, $configured);

                return [
                    'key' => $provider->key,
                    'label' => $provider->label,
                    'integration_type' => match ($provider->key) {
                        'wannads' => 'offerwall',
                        'theoremreach' => 'rewarded_survey_offerwall',
                        default => 'rewarded_survey',
                    },
                    'status' => $state['status'],
                    'status_label' => $state['label'],
                    'can_launch' => $state['can_launch'],
                    'cta' => match ($provider->key) {
                        'wannads' => 'Explore Offers',
                        'theoremreach' => 'Take Surveys',
                        default => 'Open Surveys',
                    },
                ];
            })
            ->values()
            ->all();

        return Inertia::render('surveys/index', [
            'points' => [
                'balance' => $points->balanceFor($request->user()),
                'lifetime_earned' => $points->lifetimeEarnedFor($request->user()),
            ],
            'providers' => $providers,
        ]);
    }

    /**
     * Run the provider fallback chain (or a single selected provider) and return the surveys/offers to show.
     * The entry URLs stay on the server.
     */
    public function offers(Request $request, AdProviderManager $manager): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['nullable', 'string', 'max:64'],
        ]);

        $providerKey = $validated['provider'] ?? null;

        $bundle = $providerKey
            ? $manager->requestOffersFrom(
                $request->user(),
                $providerKey,
                $providerKey === 'wannads' ? ActivityType::Offerwall : ActivityType::Survey,
                (string) $request->ip(),
                $this->deviceType((string) $request->userAgent()),
            )
            : $manager->requestOffers(
                $request->user(),
                ActivityType::Survey,
                (string) $request->ip(),
                $this->deviceType((string) $request->userAgent()),
            );

        return response()->json([
            'request_id' => $bundle->request->id,
            'provider' => $bundle->providerKey,
            // Only true in local development, where the UI offers a button to simulate the provider's confirmation.
            'simulate' => app()->environment('local'),
            'offers' => array_map(fn ($offer): array => $offer->toPublicArray(), $bundle->offers),
        ]);
    }

    /**
     * The user opened a survey: record the attempt as its own transaction and hand back the entry URL.
     */
    public function start(Request $request, AdProviderManager $manager, AdProviderRegistry $registry, ProviderIdentities $identities, ProviderComplianceGate $compliance): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => ['required', 'integer'],
            'offer_identifier' => ['required', 'string', 'max:191'],
            'purpose' => ['nullable', Rule::enum(GatePurpose::class)],
        ]);

        $user = $request->user();

        $adRequest = AdRequest::query()
            ->whereKey($validated['request_id'])
            ->where('user_id', $user->id)
            ->first();

        $provider = $adRequest?->resolved_provider_key === null
            ? null
            : AdProvider::query()->where('key', $adRequest->resolved_provider_key)->first();
        $adapter = $provider === null ? null : $registry->get($provider->key);

        if ($adRequest === null || $provider === null || $adapter === null) {
            return response()->json(['message' => 'This survey list is no longer available. Refresh to see current surveys.'], 410);
        }

        // Server-side gate: UI can_launch is not enough (direct/start after disable must fail).
        $state = $compliance->launchState($provider, $adapter->isConfigured($provider));
        if (! $state['can_launch'] || ! $provider->is_enabled) {
            return response()->json([
                'message' => $state['label'] ?: 'This provider is not available right now.',
            ], 403);
        }

        $offer = $manager->findOffer($adRequest, $validated['offer_identifier']);

        if ($offer === null) {
            return response()->json(['message' => 'That survey is no longer available. Refresh to see current surveys.'], 410);
        }

        $activityType = $provider->key === 'wannads' ? ActivityType::Offerwall : ActivityType::Survey;

        $activity = AdActivity::query()->create([
            'user_id' => $user->id,
            'ad_request_id' => $adRequest->id,
            'source' => AdActivity::SOURCE_APP,
            'provider_key' => $provider->key,
            'provider_user_id' => $identities->externalIdFor($user, $provider->key),
            'activity_type' => $activityType,
            'purpose' => $validated['purpose'] ?? null,
            'offer_identifier' => $offer->identifier,
            'offer_length_minutes' => $offer->lengthMinutes,
            'offer_category' => $offer->category,
            'status' => ActivityStatus::Started,
            'reward_status' => RewardStatus::Pending,
            'survey_loaded_at' => $adRequest->created_at,
            'survey_started_at' => now(),
            'callback_verification_status' => 'none',
        ]);

        // Most surveys do not allow a second entry, so it must not be offered again.
        $manager->forgetOffer($adRequest, $offer->identifier);

        return response()->json([
            'activity_id' => $activity->id,
            'url' => $adapter->entryUrl($provider, $offer, $activity->id),
        ]);
    }

    /**
     * Poll for the provider's confirmation. Reads only: it reports what the verified callback has already
     * recorded and can change nothing.
     */
    public function status(Request $request, AdActivity $activity): JsonResponse
    {
        abort_unless($activity->user_id === $request->user()->id, 404);

        return response()->json([
            'status' => $activity->status->value,
            'reward_status' => $activity->reward_status->value,
            'points' => $activity->awarded_points,
        ]);
    }

    /**
     * Where the provider sends the browser after a survey. Only updates the user-facing experience (and records
     * when the user came back); approval and points come exclusively from the provider's server callback.
     */
    public function return(Request $request, string $provider, AdProviderRegistry $registry): Response
    {
        $adapter = $registry->get($provider);
        $row = $adapter === null ? null : AdProvider::query()->where('key', $provider)->first();
        $activityId = null;
        $state = 'unverified';

        if ($adapter instanceof HandlesReturn && $row !== null) {
            $info = $adapter->parseReturn($row, $request);

            if ($info->verified && $info->activityId !== null) {
                $activity = AdActivity::query()
                    ->whereKey($info->activityId)
                    ->where('provider_key', $row->key)
                    ->first();

                if ($activity !== null) {
                    $activityId = $activity->id;
                    $state = 'returned';

                    $updates = ['provider_status' => $info->providerStatus];

                    if ($activity->survey_returned_at === null) {
                        $updates['survey_returned_at'] = now();
                    }

                    // Never downgrade: a confirmed (approved/rejected) transaction keeps its status.
                    if ($activity->status->isPending()) {
                        $updates['status'] = ActivityStatus::Returned;
                    }

                    $activity->forceFill($updates)->save();
                }
            }
        }

        return Inertia::render('surveys/return', ['state' => $state, 'activityId' => $activityId]);
    }

    private function deviceType(string $userAgent): int
    {
        return match (true) {
            preg_match('/iPad|Tablet|PlayBook|Silk/i', $userAgent) === 1 => OfferRequest::DEVICE_TABLET,
            preg_match('/Mobi|Android|iPhone|iPod/i', $userAgent) === 1 => OfferRequest::DEVICE_PHONE,
            default => OfferRequest::DEVICE_DESKTOP,
        };
    }
}
