import { useEffect, useMemo, useRef } from 'react';
import { sanitizeLegalHtml } from '@/lib/legal-html';
import { cn } from '@/lib/utils';

/**
 * Renders an administrator-written legal page. The HTML was already filtered on the server. It is filtered again
 * here, and the only script that ever runs is this component's: it builds a table of contents where an
 * administrator placed a contents block, using plain text nodes only.
 */
export function LegalProse({
    html,
    className,
}: {
    html: string;
    className?: string;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const clean = useMemo(() => sanitizeLegalHtml(html), [html]);

    useEffect(() => {
        const root = ref.current;

        if (!root) {
            return;
        }

        const headings = Array.from(root.querySelectorAll<HTMLElement>('h2, h3'));

        headings.forEach((heading, index) => {
            if (!heading.id) {
                heading.id = `legal-section-${index + 1}`;
            }
        });

        root.querySelectorAll<HTMLElement>('[data-pwp-toc]').forEach((block) => {
            block.replaceChildren();

            if (headings.length === 0) {
                return;
            }

            const title = document.createElement('p');
            title.className = 'legal-toc-title';
            title.textContent = 'On this page';

            const list = document.createElement('ul');

            headings.forEach((heading) => {
                const item = document.createElement('li');
                item.className = heading.tagName === 'H3' ? 'legal-toc-sub' : '';

                const link = document.createElement('a');
                link.href = `#${heading.id}`;
                link.textContent = heading.textContent ?? '';

                item.append(link);
                list.append(item);
            });

            block.append(title, list);
        });
    }, [clean]);

    return (
        <div
            ref={ref}
            className={cn('legal-prose', className)}
            dangerouslySetInnerHTML={{ __html: clean }}
        />
    );
}
