<?php

namespace App\Http\Controllers;

use App\Enums\GatePurpose;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Services\Ads\AdGate;
use App\Services\QuoteModeration;
use App\Services\QuoteRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class StudioController extends Controller
{
    public function index(Request $request, AdGate $gate): Response
    {
        $purpose = GatePurpose::QuoteGeneration;

        $quotes = Quote::query()
            ->with('category')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', [QuoteStatus::Approved, QuoteStatus::Published])
            ->latest()
            ->get()
            ->map(fn (Quote $quote): array => [
                'slug' => $quote->slug,
                'text' => $quote->text,
                'category' => $quote->category->name,
                'status' => $quote->status->value,
            ]);

        return Inertia::render('studio/index', [
            'categories' => QuoteCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'quotes' => $quotes,
            'gate' => [
                'purpose' => $purpose->value,
                'enabled' => $gate->enabled($purpose),
                'available' => $gate->available($request->user(), $purpose),
            ],
        ]);
    }

    /**
     * A quote needs one completed (provider-confirmed) survey. The unlock is spent before generating and given
     * back if generation fails, so a failure on our side never costs the user a survey.
     */
    public function generate(Request $request, QuoteRouter $router, AdGate $gate): RedirectResponse
    {
        $validated = $request->validate([
            'quote_category_id' => ['required', 'exists:quote_categories,id'],
        ]);

        $category = QuoteCategory::query()->whereKey($validated['quote_category_id'])->firstOrFail();
        $user = $request->user();
        $purpose = GatePurpose::QuoteGeneration;

        $unlock = null;

        if ($gate->enabled($purpose)) {
            $unlock = $gate->claim($user, $purpose);

            if ($unlock === null) {
                throw ValidationException::withMessages([
                    'ad_gate' => 'Complete a survey first to create a quote.',
                ]);
            }
        }

        try {
            $quote = $router->generate($user, $category);
        } catch (Throwable $exception) {
            if ($unlock !== null) {
                $gate->release($unlock);
            }

            throw $exception;
        }

        if ($unlock !== null) {
            $gate->attach($unlock, $quote);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Quote ready — publish it when you want it on the feed.',
        ]);

        return redirect()->route('studio');
    }

    public function publish(Request $request, Quote $quote, QuoteModeration $moderation): RedirectResponse
    {
        $this->authorize('publish', $quote);

        $moderation->publish($quote);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Quote published to the feed.',
        ]);

        return redirect()->route('studio');
    }
}
