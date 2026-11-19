<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Support\LegalDefaults;
use App\Support\LegalHtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LegalDocumentController extends Controller
{
    public function __construct(private LegalHtmlSanitizer $sanitizer) {}

    public function edit(string $slug): Response
    {
        $document = $this->document($slug);

        return Inertia::render('admin/settings/legal-editor', [
            'slug' => $slug,
            'title' => $document->title,
            // Sanitize on the way out as well, so nothing stored earlier can reach the editor unfiltered.
            'html' => $this->sanitizer->sanitize($document->content_html ?: LegalDefaults::html($slug)),
            'isCustom' => $document->isCustom(),
            'updatedAt' => $document->updated_at?->toIso8601String(),
            'updatedBy' => $document->editor?->name,
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $document = $this->document($slug);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'content' => ['required', 'string', 'max:600000'],
        ]);

        // The browser editor filters too, but only this pass is trusted. Whatever was sent, only safe HTML is stored.
        $clean = $this->sanitizer->sanitize($validated['content']);

        if ($clean === '' || trim(strip_tags($clean, '<img><hr>')) === '' && ! str_contains($clean, '<img') && ! str_contains($clean, '<hr')) {
            throw ValidationException::withMessages([
                'content' => 'The page is empty after removing unsafe content. Write some text first.',
            ]);
        }

        $document->fill([
            'title' => $validated['title'],
            'content_html' => $clean,
            'updated_by' => $request->user()->id,
            'published_at' => now(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => $document->title.' was published.']);

        return back();
    }

    /**
     * Go back to the built-in starter text.
     */
    public function reset(string $slug): RedirectResponse
    {
        $document = $this->document($slug);

        if ($document->exists) {
            $document->fill(['content_html' => null, 'published_at' => null, 'title' => LegalDefaults::title($slug)])->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Restored the default text.']);

        return back();
    }

    /**
     * Store an image for use inside a legal page and return its public address.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => [
                'required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048',
                'dimensions:min_width=16,min_height=16,max_width=4000,max_height=4000',
            ],
        ]);

        $path = $request->file('image')->store('legal', 'public');

        return response()->json(['url' => '/storage/'.$path]);
    }

    private function document(string $slug): LegalDocument
    {
        return LegalDocument::forSlug($slug) ?? abort(404);
    }
}
