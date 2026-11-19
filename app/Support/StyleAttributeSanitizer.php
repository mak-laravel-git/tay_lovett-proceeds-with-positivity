<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Attribute rules for rich legal text, beyond what the generic sanitizer knows.
 *
 * - style: only text-align, color and background-color, each with a strictly checked value. Nothing that can load
 *   a resource (url(), image-set, @import) or run script (expression, javascript:) can pass.
 * - id: only on headings, forced to start with "legal-" so it can never shadow a page element or global name.
 * - colspan, rowspan, width, height: small whole numbers only.
 * - target: only _blank. data-type, data-checked and data-pwp-toc: fixed values our own styles and script read.
 */
class StyleAttributeSanitizer implements AttributeSanitizerInterface
{
    private const COLOR = '/^(?:#[0-9a-f]{3,8}|(?:rgb|hsl)a?\(\s*[0-9.%\s,\/]{1,40}\)|[a-z]{3,20})$/i';

    public function getSupportedElements(): ?array
    {
        return null;
    }

    public function getSupportedAttributes(): ?array
    {
        return ['style', 'id', 'colspan', 'rowspan', 'width', 'height', 'target', 'data-type', 'data-checked', 'data-pwp-toc'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $value = trim($value);

        return match ($attribute) {
            'style' => $this->style($value),
            'id' => $this->id($value),
            'colspan', 'rowspan' => preg_match('/^[1-9]\d?$/', $value) === 1 ? $value : null,
            'width', 'height' => preg_match('/^[1-9]\d{0,3}$/', $value) === 1 ? $value : null,
            'target' => $value === '_blank' ? $value : null,
            'data-type' => in_array($value, ['taskList', 'taskItem', 'detailsContent'], true) ? $value : null,
            'data-checked' => in_array($value, ['true', 'false'], true) ? $value : null,
            'data-pwp-toc' => $value === 'true' ? $value : null,
            default => null,
        };
    }

    private function style(string $style): ?string
    {
        $clean = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

            if ($value === '' || preg_match('/[\\\\<>@"\'`{}]|url\s*\(|expression|javascript|\/\*/i', $value) === 1) {
                continue;
            }

            $allowed = match ($property) {
                'text-align' => in_array(strtolower($value), ['left', 'right', 'center', 'justify'], true),
                'color', 'background-color' => preg_match(self::COLOR, $value) === 1,
                default => false,
            };

            if ($allowed) {
                $clean[$property] = $value;
            }
        }

        if ($clean === []) {
            return null;
        }

        return implode('; ', array_map(fn (string $property, string $value): string => $property.': '.$value, array_keys($clean), $clean));
    }

    private function id(string $id): ?string
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,59}$/', $id) !== 1) {
            return null;
        }

        return str_starts_with($id, 'legal-') ? $id : 'legal-'.$id;
    }
}
