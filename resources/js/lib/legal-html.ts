import DOMPurify from 'dompurify';

/**
 * Browser-side filter for legal page HTML. It mirrors the server allow-list (App\Support\LegalHtmlSanitizer) as a
 * second layer: the editor, the HTML tab and the preview all pass through it. The server filter is the trusted one.
 */

const TAGS = [
    'p', 'br', 'hr', 'div', 'span', 'blockquote', 'pre', 'code',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'strong', 'b', 'em', 'i', 'u', 's', 'del', 'mark', 'sub', 'sup', 'small',
    'ul', 'ol', 'li', 'label',
    'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
    'details', 'summary', 'a', 'img',
];

const ATTRIBUTES = [
    'href', 'title', 'target', 'rel', 'src', 'alt', 'width', 'height', 'loading', 'referrerpolicy',
    'style', 'id', 'colspan', 'rowspan', 'open', 'data-type', 'data-checked', 'data-pwp-toc',
];

const HEADING = /^H[1-6]$/;
const COLOR = /^(?:#[0-9a-f]{3,8}|(?:rgb|hsl)a?\(\s*[0-9.%\s,/]{1,40}\)|[a-z]{3,20})$/i;

function safeStyle(style: string): string {
    const kept: string[] = [];

    for (const declaration of style.split(';')) {
        const [property, ...rest] = declaration.split(':');
        const name = property?.trim().toLowerCase();
        const value = rest.join(':').replace(/\s+/g, ' ').trim();

        if (!name || !value || /[\\<>@"'`{}]|url\s*\(|expression|javascript|\/\*/i.test(value)) {
            continue;
        }

        if (
            (name === 'text-align' && ['left', 'right', 'center', 'justify'].includes(value.toLowerCase())) ||
            ((name === 'color' || name === 'background-color') && COLOR.test(value))
        ) {
            kept.push(`${name}: ${value}`);
        }
    }

    return kept.join('; ');
}

let ready = false;

function prepare(): void {
    if (ready) {
        return;
    }

    ready = true;

    DOMPurify.addHook('uponSanitizeAttribute', (_node, data) => {
        if (data.attrName === 'src' && /^\s*data:/i.test(data.attrValue)) {
            data.keepAttr = false;
        }
    });

    DOMPurify.addHook('afterSanitizeAttributes', (node) => {
        if (!(node instanceof Element)) {
            return;
        }

        const style = node.getAttribute('style');

        if (style !== null) {
            const clean = safeStyle(style);
            clean ? node.setAttribute('style', clean) : node.removeAttribute('style');
        }

        const id = node.getAttribute('id');

        if (id !== null) {
            if (HEADING.test(node.tagName) && /^[A-Za-z][A-Za-z0-9_-]{0,59}$/.test(id)) {
                node.setAttribute('id', id.startsWith('legal-') ? id : `legal-${id}`);
            } else {
                node.removeAttribute('id');
            }
        }

        const type = node.getAttribute('data-type');

        if (type !== null && !['taskList', 'taskItem', 'detailsContent'].includes(type)) {
            node.removeAttribute('data-type');
        }

        if (node.hasAttribute('data-pwp-toc') && node.getAttribute('data-pwp-toc') !== 'true') {
            node.removeAttribute('data-pwp-toc');
        }

        if (node.tagName === 'A') {
            node.setAttribute('rel', 'noopener noreferrer nofollow');

            if (node.getAttribute('target') !== '_blank') {
                node.removeAttribute('target');
            }
        }

        if (node.tagName === 'IMG') {
            node.setAttribute('loading', 'lazy');
            node.setAttribute('referrerpolicy', 'no-referrer');
        }
    });
}

export function sanitizeLegalHtml(html: string): string {
    prepare();

    return DOMPurify.sanitize(html, {
        ALLOWED_TAGS: TAGS,
        ALLOWED_ATTR: ATTRIBUTES,
        ALLOW_DATA_ATTR: false,
        ALLOW_ARIA_ATTR: false,
        ALLOWED_URI_REGEXP: /^(?:https?:|mailto:|tel:|#|\/(?!\/))/i,
        FORBID_TAGS: ['style', 'script', 'iframe', 'form', 'input', 'object', 'embed', 'svg', 'math', 'meta', 'link', 'base'],
        KEEP_CONTENT: false,
    });
}
