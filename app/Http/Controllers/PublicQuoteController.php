<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\AppSettings;
use App\Services\OgMeta;
use App\Services\QuoteOgImage;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicQuoteController extends Controller
{
    /**
     * Show one published quote. Unpublished quotes stay private.
     */
    public function show(Quote $quote, OgMeta $meta): Response
    {
        abort_unless($quote->isPublished(), 404);
        $quote->load('category');

        // Rendered server-side in app.blade.php so social crawlers (no JS) see it.
        view()->share('og', $meta->forQuote($quote));

        return Inertia::render('quotes/public', [
            'quote' => [
                'slug' => $quote->slug,
                'text' => $quote->text,
                'category' => $quote->category->name,
            ],
        ]);
    }

    /**
     * The 1200x630 share card for a published quote.
     */
    public function image(Quote $quote, QuoteOgImage $images): HttpResponse
    {
        abort_unless($quote->isPublished(), 404);

        $png = $images->png($quote, app(AppSettings::class)->site()['name']);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * The default 1200x630 share card used by every page that is not a quote.
     */
    public function siteImage(QuoteOgImage $images): HttpResponse
    {
        $site = app(AppSettings::class)->site();

        return response($images->siteCard($site['name'], $site['description']), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
