<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use App\Support\LegalDefaults;
use App\Support\LegalHtmlSanitizer;
use Inertia\Inertia;
use Inertia\Response;

class PublicLegalController extends Controller
{
    public function show(string $slug, LegalHtmlSanitizer $sanitizer): Response
    {
        $document = LegalDocument::forSlug($slug) ?? abort(404);
        $custom = $document->isCustom();

        return Inertia::render('legal/show', [
            'title' => $document->title,
            // Filtered again at render time, so even a bad row written by hand never reaches a visitor.
            'html' => $sanitizer->sanitize($custom ? $document->content_html : LegalDefaults::html($slug)),
            'isDefault' => ! $custom,
            'updatedAt' => $custom ? $document->published_at?->toIso8601String() : null,
        ]);
    }
}
