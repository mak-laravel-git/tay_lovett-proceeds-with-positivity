import CharacterCount from '@tiptap/extension-character-count';
import Placeholder from '@tiptap/extension-placeholder';
import { Details, DetailsContent, DetailsSummary } from '@tiptap/extension-details';
import Highlight from '@tiptap/extension-highlight';
import Image from '@tiptap/extension-image';
import { TaskItem, TaskList } from '@tiptap/extension-list';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import { Table, TableCell, TableHeader, TableRow } from '@tiptap/extension-table';
import { Color, TextStyle } from '@tiptap/extension-text-style';
import TextAlign from '@tiptap/extension-text-align';
import Typography from '@tiptap/extension-typography';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { EditorToolbar } from '@/components/legal/editor-toolbar';
import { TocBlock } from '@/components/legal/toc-block';
import { sanitizeLegalHtml } from '@/lib/legal-html';

/**
 * The visual editor tab. Everything it can produce is inside the allow-list of the server filter.
 */
export default function RichEditor({
    value,
    onChange,
}: {
    value: string;
    onChange: (html: string) => void;
}) {
    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: { levels: [1, 2, 3, 4, 5, 6] },
                link: {
                    openOnClick: false,
                    autolink: true,
                    HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
                },
            }),
            TextStyle,
            Color,
            Highlight,
            Subscript,
            Superscript,
            TextAlign.configure({ types: ['heading', 'paragraph'] }),
            Image.configure({ inline: false, allowBase64: false }),
            Table.configure({ resizable: false }),
            TableRow,
            TableHeader,
            TableCell,
            TaskList,
            TaskItem.configure({ nested: true }),
            Details.configure({ persist: true }),
            DetailsSummary,
            DetailsContent,
            TocBlock,
            Typography,
            Placeholder.configure({ placeholder: 'Start writing your page...' }),
            CharacterCount.configure({ limit: 200000 }),
        ],
        content: sanitizeLegalHtml(value),
        editorProps: {
            attributes: {
                class: 'legal-prose-editor focus:outline-none',
                'aria-label': 'Page content',
            },
        },
        onUpdate: ({ editor: current }) => onChange(current.getHTML()),
        immediatelyRender: false,
    });

    if (!editor) {
        return null;
    }

    return (
        <div className="overflow-hidden rounded-2xl border border-line bg-white">
            <EditorToolbar editor={editor} />
            <div className="max-h-[70vh] min-h-[26rem] overflow-y-auto p-5">
                <div className="legal-prose">
                    <EditorContent editor={editor} />
                </div>
            </div>
            <div className="flex items-center justify-between border-t border-line bg-sky-wash px-4 py-2 text-xs text-muted-ink">
                <span>
                    {editor.storage.characterCount.words()} words ·{' '}
                    {editor.storage.characterCount.characters()} characters
                </span>
                <span>Unsafe content is removed when you save.</span>
            </div>
        </div>
    );
}
