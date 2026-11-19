<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\QuoteCategory;
use App\Services\QuoteModeration;
use App\Support\QuoteText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QuoteModerationController extends Controller
{
    /**
     * List quotes waiting for an administrator.
     */
    public function index(): Response
    {
        $quotes = Quote::query()
            ->with('category')
            ->latest()
            ->get()
            ->map(fn (Quote $quote): array => [
                'slug' => $quote->slug,
                'text' => $quote->text,
                'category' => $quote->category->name,
                'status' => $quote->status->value,
                'rejection_reason' => $quote->rejection_reason,
            ]);

        return Inertia::render('admin/quotes', [
            'quotes' => $quotes,
            'categories' => QuoteCategory::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Store a quote in review. The request cannot choose a public status.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'quote_category_id' => ['required', 'exists:quote_categories,id'],
            'text' => ['required', 'string', 'max:500'],
        ]);

        $hash = QuoteText::hash($validated['text']);

        if (Quote::query()->where('normalized_hash', $hash)->exists()) {
            throw ValidationException::withMessages([
                'text' => 'This quote already exists.',
            ]);
        }

        Quote::query()->create([
            'quote_category_id' => $validated['quote_category_id'],
            'user_id' => $request->user()->id,
            'text' => trim($validated['text']),
            'normalized_hash' => $hash,
            'status' => QuoteStatus::PendingReview,
        ]);

        return back();
    }

    public function approve(Quote $quote, QuoteModeration $moderation): RedirectResponse
    {
        $moderation->approve($quote);

        return back();
    }

    public function reject(Request $request, Quote $quote, QuoteModeration $moderation): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $moderation->reject($quote, $validated['reason']);

        return back();
    }

    public function publish(Quote $quote, QuoteModeration $moderation): RedirectResponse
    {
        $moderation->publish($quote);

        return back();
    }

    public function unpublish(Quote $quote, QuoteModeration $moderation): RedirectResponse
    {
        $moderation->unpublish($quote);

        return back();
    }
}
