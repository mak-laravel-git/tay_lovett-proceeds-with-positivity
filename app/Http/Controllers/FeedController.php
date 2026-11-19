<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\Ads\PointLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    /**
     * Show published quotes.
     */
    public function index(Request $request, PointLedger $points): Response
    {
        $savedIds = $request->user()
            ->savedQuotes()
            ->pluck('quotes.id');

        $quotes = Quote::query()
            ->with('category')
            ->where('status', 'published')
            ->latest('published_at')
            ->get()
            ->map(fn (Quote $quote): array => [
                'slug' => $quote->slug,
                'text' => $quote->text,
                'category' => $quote->category->name,
                'saved' => $savedIds->contains($quote->id),
            ]);

        return Inertia::render('feed/index', [
            'quotes' => $quotes,
            'points' => [
                'balance' => $points->balanceFor($request->user()),
                'lifetime_earned' => $points->lifetimeEarnedFor($request->user()),
            ],
        ]);
    }

    /**
     * Show quotes the user has saved.
     */
    public function saved(Request $request): Response
    {
        $quotes = $request->user()
            ->savedQuotes()
            ->where('quotes.status', 'published')
            ->get()
            ->map(fn (Quote $quote): array => [
                'slug' => $quote->slug,
                'text' => $quote->text,
                'category' => $quote->category->name,
            ]);

        return Inertia::render('saved/index', [
            'quotes' => $quotes,
        ]);
    }

    /**
     * Save a published quote for the current user.
     */
    public function save(Request $request, Quote $quote): RedirectResponse
    {
        abort_unless($request->user()->can('save', $quote), 404);

        $request->user()->savedQuotes()->syncWithoutDetaching([$quote->id]);

        return back();
    }
}
