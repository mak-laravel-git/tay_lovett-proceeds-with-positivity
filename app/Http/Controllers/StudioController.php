<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Models\QuoteWorkflowSetting;
use App\Services\QuoteModeration;
use App\Services\QuoteRouter;
use App\Services\QuoteWorkflow\WorkflowPresenter;
use App\Services\QuoteWorkflow\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudioController extends Controller
{
    public function index(Request $request, WorkflowService $workflows, WorkflowPresenter $presenter): Response
    {
        $quotes = Quote::query()
            ->with('category')
            ->where('user_id', $request->user()->id)
            // A quote still waiting for its last workflow step is never listed, so its text stays on the server.
            ->whereIn('status', [QuoteStatus::Approved, QuoteStatus::Published])
            ->latest()
            ->get()
            ->map(fn (Quote $quote): array => [
                'slug' => $quote->slug,
                'text' => $quote->text,
                'category' => $quote->category->name,
                'status' => $quote->status->value,
            ]);

        $rules = QuoteWorkflowSetting::current();
        $active = $workflows->activeFor($request->user());

        return Inertia::render('studio/index', [
            'categories' => QuoteCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'quotes' => $quotes,
            'workflow' => [
                // A run that is already in progress is finished even if the flow has been switched off since.
                'enabled' => $rules->enabled || $active !== null,
                'active' => $active === null ? null : $presenter->present($active),
                'disclosure' => $presenter->disclosure($active === null ? $rules->snapshot() : $active->settings_snapshot),
            ],
        ]);
    }

    /**
     * One-step creation, only available while the step-by-step flow is switched off. While it is on, this
     * address is refused so the steps cannot be skipped.
     */
    public function generate(Request $request, QuoteRouter $router): RedirectResponse
    {
        if (QuoteWorkflowSetting::current()->enabled) {
            throw ValidationException::withMessages([
                'workflow' => 'Use the Create quote steps on this page.',
            ]);
        }

        $validated = $request->validate([
            'quote_category_id' => ['required', 'exists:quote_categories,id'],
        ]);

        $category = QuoteCategory::query()->whereKey($validated['quote_category_id'])->firstOrFail();

        $router->generate($request->user(), $category);

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
