<?php

namespace App\Services;

use App\Models\Quote;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Renders the 1200x630 share card (PNG) shown when a quote link is shared.
 */
class QuoteOgImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private const PADDING = 90;

    /**
     * Return PNG bytes for the quote, cached on disk by id + content hash.
     */
    public function png(Quote $quote, string $siteName): string
    {
        $quote->loadMissing('category');
        $category = (string) $quote->category?->name;

        $key = 'og/'.$quote->slug.'-'.substr(sha1($quote->text.'|'.$category.'|'.$siteName.'|v2'), 0, 12).'.png';

        return $this->cached($key, fn (): string => $this->render($quote->text, $category, $siteName));
    }

    /**
     * Return PNG bytes for the site-wide default share card (used by every non-quote page).
     */
    public function siteCard(string $siteName, string $description): string
    {
        $key = 'og/site-'.substr(sha1($siteName.'|'.$description.'|v1'), 0, 12).'.png';

        return $this->cached($key, fn (): string => $this->renderSite($siteName, $description));
    }

    /**
     * @param  callable(): string  $render
     */
    private function cached(string $key, callable $render): string
    {
        $disk = Storage::disk('local');

        if ($disk->exists($key)) {
            return $disk->get($key);
        }

        $png = $render();
        $disk->put($key, $png);

        return $png;
    }

    private function renderSite(string $siteName, string $description): string
    {
        [$serif, $sans] = $this->fonts();
        $img = $this->canvas();

        $ink = $this->color($img, 22, 41, 51);
        $accent = $this->color($img, 14, 116, 144);
        $muted = $this->color($img, 90, 112, 122);

        imagefilledrectangle($img, 0, 0, 16, self::HEIGHT, $accent);
        imagettftext($img, 150, 0, self::PADDING - 8, 250, $accent, $serif, "\u{201C}");

        $maxWidth = self::WIDTH - self::PADDING * 2;
        $name = $this->wrap(trim($siteName), $serif, 60, $maxWidth);
        $y = 330;
        foreach (array_slice($name, 0, 2) as $line) {
            imagettftext($img, 60, 0, self::PADDING, $y, $ink, $serif, $line);
            $y += 80;
        }

        $description = trim(preg_replace('/\s+/', ' ', $description));
        if ($description !== '') {
            $lines = array_slice($this->wrap($description, $sans, 26, $maxWidth), 0, 3);
            $y = max($y, 400);
            foreach ($lines as $line) {
                imagettftext($img, 26, 0, self::PADDING, $y, $muted, $sans, $line);
                $y += 40;
            }
        }

        imagefilledrectangle($img, self::PADDING, self::HEIGHT - 95, self::PADDING + 60, self::HEIGHT - 92, $accent);

        return $this->encode($img);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fonts(): array
    {
        $serif = resource_path('fonts/Lora.ttf');
        $sans = resource_path('fonts/Inter.ttf');

        if (! function_exists('imagettftext') || ! is_file($serif) || ! is_file($sans)) {
            throw new RuntimeException('GD with FreeType and the bundled fonts are required.');
        }

        return [$serif, $sans];
    }

    private function canvas(): \GdImage
    {
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($img, true);

        // Vertical gradient background (light brand blue -> white).
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t = $y / self::HEIGHT;
            $c = $this->color(
                $img,
                (int) (237 + (255 - 237) * $t),
                (int) (247 + (255 - 247) * $t),
                (int) (250 + (255 - 250) * $t),
            );
            imageline($img, 0, $y, self::WIDTH, $y, $c);
        }

        return $img;
    }

    private function color(\GdImage $img, int $red, int $green, int $blue): int
    {
        $color = imagecolorallocate(
            $img,
            max(0, min(255, $red)),
            max(0, min(255, $green)),
            max(0, min(255, $blue)),
        );

        if ($color === false) {
            throw new RuntimeException('Unable to allocate a color for the share image.');
        }

        return $color;
    }

    private function encode(\GdImage $img): string
    {
        ob_start();
        imagepng($img, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    private function render(string $text, string $category, string $siteName): string
    {
        [$serif, $sans] = $this->fonts();
        $img = $this->canvas();

        $ink = $this->color($img, 22, 41, 51);
        $accent = $this->color($img, 14, 116, 144);
        $muted = $this->color($img, 90, 112, 122);

        // Accent bar on the left edge.
        imagefilledrectangle($img, 0, 0, 16, self::HEIGHT, $accent);

        // Category label.
        if ($category !== '') {
            imagettftext($img, 22, 0, self::PADDING, 80, $accent, $sans, mb_strtoupper($category));
        }

        // Large opening quote mark.
        imagettftext($img, 150, 0, self::PADDING - 8, 270, $accent, $serif, "\u{201C}");

        // Quote text: shrink the font until the wrapped text fits.
        $maxWidth = self::WIDTH - self::PADDING * 2;
        $top = 200;
        $bottom = self::HEIGHT - 130;
        $text = trim(preg_replace('/\s+/', ' ', $text));

        $size = 56;
        $lines = [];
        $lineHeight = 0;

        for (; $size >= 24; $size -= 2) {
            $lines = $this->wrap($text, $serif, $size, $maxWidth);
            $lineHeight = (int) round($size * 1.35);

            if (count($lines) * $lineHeight <= $bottom - $top) {
                break;
            }
        }

        // If still too tall at the minimum size, truncate with an ellipsis.
        $maxLines = max(1, intdiv($bottom - $top, $lineHeight));
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1], ' .,;:').'…';
        }

        $y = $top + $size;
        foreach ($lines as $line) {
            imagettftext($img, $size, 0, self::PADDING, $y, $ink, $serif, $line);
            $y += $lineHeight;
        }

        // Footer: site name.
        imagefilledrectangle($img, self::PADDING, self::HEIGHT - 95, self::PADDING + 60, self::HEIGHT - 92, $accent);
        imagettftext($img, 24, 0, self::PADDING, self::HEIGHT - 50, $muted, $sans, $siteName);

        return $this->encode($img);
    }

    /**
     * Greedy word-wrap measured with the actual font.
     *
     * @return list<string>
     */
    private function wrap(string $text, string $font, int $size, int $maxWidth): array
    {
        $lines = [];
        $current = '';

        foreach (explode(' ', $text) as $word) {
            $try = $current === '' ? $word : $current.' '.$word;

            if ($current !== '' && $this->width($try, $font, $size) > $maxWidth) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $try;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function width(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box === false ? 0 : abs($box[2] - $box[0]);
    }
}
