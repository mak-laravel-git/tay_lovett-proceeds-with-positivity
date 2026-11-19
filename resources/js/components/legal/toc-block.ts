import { Node } from '@tiptap/react';

/**
 * A marker block. On the public page it becomes a table of contents built from the page's headings,
 * by LegalProse. Inside the editor it only shows a label.
 */
export const TocBlock = Node.create({
    name: 'tocBlock',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: true,

    parseHTML() {
        return [{ tag: 'div[data-pwp-toc]' }];
    },

    renderHTML() {
        return ['div', { 'data-pwp-toc': 'true' }];
    },
});
