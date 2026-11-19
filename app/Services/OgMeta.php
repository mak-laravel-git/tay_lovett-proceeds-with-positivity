<?php

namespace App\Services;

use App\Models\Quote;
use Illuminate\Support\Str;

/**
 * Builds the Open Graph / Twitter card data rendered server-side in app.blade.php,
 * so link previews work for crawlers that do not run JavaScript.
 */
class OgMeta
{
    public function __construct(private AppSettings $settings) {}

    /**
     * Preview data for a published quote: its text on the generated share card.
     *
     * @return array<string, string|bool>
     */
    public function forQuote(Quote $quote): array
    {
        $siteName = $this->settings->site()['name'];

        return [
            'type' => 'article',
            'canonical' => true,
            'title' => Str::limit($quote->text, 90).' — '.$siteName,
            'description' => $quote->text,
            'url' => route('quotes.public', $quote),
            'image' => route('quotes.og', [$quote, 'v' => $quote->updated_at?->timestamp]),
            'image_alt' => $quote->text,
            'site_name' => $siteName,
        ];
    }

    /**
     * Site-wide fallback so every other page (login, privacy, home...) still renders a preview card.
     *
     * @return array<string, string|bool>
     */
    public function default(): array
    {
        $site = $this->settings->site();
        $description = $site['description'] !== '' ? $site['description'] : $site['name'];

        return [
            'type' => 'website',
            'canonical' => false,
            'title' => $site['name'],
            'description' => $description,
            'url' => url()->current(),
            'image' => route('site.og', ['v' => substr(sha1($site['name'].'|'.$description), 0, 8)]),
            'image_alt' => $site['name'],
            'site_name' => $site['name'],
        ];
    }
}
