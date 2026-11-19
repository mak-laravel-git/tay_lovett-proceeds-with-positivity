<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Services\QuoteModeration;
use App\Services\QuoteRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudioController extends Controller
{
    public function index(Request $request): Response
    {
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
        ]);
    }

    public function generate(Request $request, QuoteRouter $router): RedirectResponse
    {
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
