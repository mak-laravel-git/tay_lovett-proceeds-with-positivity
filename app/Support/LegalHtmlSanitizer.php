<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * The one authoritative filter for administrator-written HTML (Privacy Policy, Terms of Service).
 *
 * It is an allow-list. Anything not named here is removed, and unknown elements are dropped together with their
 * content. Scripts, styles, frames, forms, embeds, SVG and every event handler never survive, and neither do
 * javascript: or data: addresses. The browser editor runs a matching filter, but this one is what protects readers.
 */
class LegalHtmlSanitizer
{
    /**
     * Elements that may carry no attribute except the ones listed in ATTRIBUTES.
     *
     * @var list<string>
     */
    private const ELEMENTS = [
        'p', 'br', 'hr', 'div', 'span', 'blockquote', 'pre', 'code',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'del', 'mark', 'sub', 'sup', 'small',
        'ul', 'ol', 'li', 'label',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
        'details', 'summary',
    ];

    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Drop)
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['https'])
            ->allowRelativeMedias()
            ->withMaxInputLength(500_000)
            ->withAttributeSanitizer(new StyleAttributeSanitizer);

        foreach (self::ELEMENTS as $element) {
            $config = $config->allowElement($element, $this->attributesFor($element));
        }

        $config = $config
            ->allowElement('a', ['href', 'title', 'target', 'rel'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height', 'loading', 'referrerpolicy'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->forceAttribute('img', 'loading', 'lazy')
            ->forceAttribute('img', 'referrerpolicy', 'no-referrer');

        $this->sanitizer = new HtmlSanitizer($config);
    }

    /**
     * Return safe HTML for any input. Running it again on its own output changes nothing.
     */
    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        return trim($this->sanitizer->sanitizeFor('body', $html));
    }

    /**
     * @return list<string>
     */
    private function attributesFor(string $element): array
    {
        $attributes = ['style'];

        return match (true) {
            in_array($element, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) => [...$attributes, 'id'],
            in_array($element, ['th', 'td'], true) => [...$attributes, 'colspan', 'rowspan'],
            in_array($element, ['col'], true) => ['width'],
            $element === 'ul' => [...$attributes, 'data-type'],
            $element === 'li' => [...$attributes, 'data-type', 'data-checked'],
            $element === 'div' => [...$attributes, 'data-type', 'data-pwp-toc'],
            $element === 'details' => ['open'],
            default => $attributes,
        };
    }
}
