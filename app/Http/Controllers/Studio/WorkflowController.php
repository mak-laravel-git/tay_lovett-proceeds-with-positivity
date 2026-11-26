<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\QuoteCategory;
use App\Models\QuoteWorkflow;
use App\Models\RewardedAdAttempt;
use App\Services\Ads\Data\Offer;
use App\Services\Ads\Data\OfferRequest;
use App\Services\QuoteWorkflow\RewardedAdService;
use App\Services\QuoteWorkflow\WorkflowPresenter;
use App\Services\QuoteWorkflow\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The JSON endpoints behind the step-by-step "Create quote" flow. Every response carries the run as the server
 * sees it; the page never decides the state itself.
 */
class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflows,
        private readonly WorkflowPresenter $presenter,
    ) {}

    /**
     * Start a run, or resume the user's unfinished one.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_category_id' => ['required', 'integer', Rule::exists('quote_categories', 'id')->where('is_active', true)],
        ]);

        $category = QuoteCategory::query()->whereKey($validated['quote_category_id'])->firstOrFail();
        $workflow = $this->workflows->start($request->user(), $category);

        return $this->respond($workflow);
    }

    public function show(Request $request, QuoteWorkflow $workflow): JsonResponse
    {
        $this->ensureOwner($request, $workflow);

        return $this->respond($this->workflows->settle($workflow));
    }

    /**
     * Get the ad to show for the current step.
     */
    public function issueAd(Request $request, QuoteWorkflow $workflow): JsonResponse
    {
        $this->ensureOwner($request, $workflow);

        $prepared = $this->workflows->prepareAd($workflow, $request->ip(), $request->userAgent());
        $attempt = $prepared['attempt'];
        $unit = $prepared['unit'];

        return $this->respond($workflow, [
            'ad' => $attempt === null || $unit === null ? null : [
                'attempt_id' => $attempt->id,
                'ad_unit_path' => $unit['ad_unit_path'],
                'load_timeout_seconds' => $unit['load_timeout_seconds'],
                'min_watch_seconds' => $unit['min_watch_seconds'],
            ],
            'message' => $prepared['message'],
        ]);
    }

    /**
     * The page reports what happened to the ad it was given.
     */
    public function adEvent(Request $request, QuoteWorkflow $workflow, RewardedAdAttempt $attempt): JsonResponse
    {
        $this->ensureOwner($request, $workflow);
        abort_unless($attempt->quote_workflow_id === $workflow->id, 404);

        $validated = $request->validate([
            'event' => ['required', Rule::in(RewardedAdService::EVENTS)],
            'detail' => ['nullable', 'string', 'max:191'],
        ]);

        $result = $this->workflows->handleAdEvent($workflow, $attempt, $validated['event'], $validated['detail'] ?? null);

        return $this->respond($workflow, [
            'attempt_status' => $result['attempt']->status->value,
            'message' => $result['message'],
        ]);
    }

    /**
     * The surveys available for the survey step. Entry links stay on the server until one is started.
     */
    public function offers(Request $request, QuoteWorkflow $workflow): JsonResponse
    {
        $this->ensureOwner($request, $workflow);

        $result = $this->workflows->surveyOffers($workflow, (string) $request->ip(), OfferRequest::deviceTypeFor((string) $request->userAgent()));
        $bundle = $result['bundle'];

        return $this->respond($workflow, [
            'request_id' => $bundle === null ? 0 : $bundle->request->id,
            'provider' => $bundle?->providerKey,
            'simulate' => app()->environment('local'),
            'offers' => $bundle === null ? [] : array_map(fn (Offer $offer): array => $offer->toPublicArray(), $bundle->offers),
            'message' => $result['message'],
        ]);
    }

    public function generate(Request $request, QuoteWorkflow $workflow): JsonResponse
    {
        $this->ensureOwner($request, $workflow);

        return $this->respond($this->workflows->generate($workflow));
    }

    public function cancel(Request $request, QuoteWorkflow $workflow): JsonResponse
    {
        $this->ensureOwner($request, $workflow);

        return $this->respond($this->workflows->cancel($workflow));
    }

    private function ensureOwner(Request $request, QuoteWorkflow $workflow): void
    {
        abort_unless($workflow->user_id === $request->user()->id, 404);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function respond(QuoteWorkflow $workflow, array $extra = []): JsonResponse
    {
        return response()->json([...$extra, 'workflow' => $this->presenter->present($workflow->refresh())]);
    }
}
