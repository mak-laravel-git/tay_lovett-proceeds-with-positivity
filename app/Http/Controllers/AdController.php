<?php

namespace App\Http\Controllers;

use App\Models\AdSession;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdController extends Controller
{
    /**
     * Present a labeled demonstration advertisement. It does not create revenue.
     */
    public function present(Request $request, Quote $quote): RedirectResponse
    {
        abort_unless($quote->isPublished(), 404);

        $session = AdSession::query()->create([
            'user_id' => $request->user()->id,
            'quote_id' => $quote->id,
            'state' => 'ad_presented',
            'provider' => 'demo',
        ]);

        return redirect()->route('ads.show', $session);
    }

    public function show(Request $request, AdSession $ad): Response
    {
        abort_unless($ad->user_id === $request->user()->id, 404);

        return Inertia::render('ads/show', [
            'ad' => [
                'id' => $ad->id,
                'provider' => 'demo',
                'label' => 'Demonstration advertisement. This is not a paid impression.',
            ],
        ]);
    }

    public function exit(Request $request, AdSession $ad): RedirectResponse
    {
        abort_unless($ad->user_id === $request->user()->id, 404);

        $ad->update(['state' => 'ad_exited']);

        $next = Quote::query()
            ->where('status', 'published')
            ->where('id', '!=', $ad->quote_id)
            ->inRandomOrder()
            ->first();

        return redirect()->route($next ? 'quotes.public' : 'feed', $next ? ['quote' => $next] : []);
    }
}
