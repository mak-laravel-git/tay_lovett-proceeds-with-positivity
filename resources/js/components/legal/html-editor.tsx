import { html } from '@codemirror/lang-html';
import { EditorView } from '@codemirror/view';
import CodeMirror from '@uiw/react-codemirror';
import { TriangleAlert, WandSparkles } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { sanitizeLegalHtml } from '@/lib/legal-html';

const RISKY = /<\s*(script|iframe|object|embed|form|style|svg|meta|link|base)\b|\son\w+\s*=|javascript:|data:/i;

/**
 * The HTML tab: edit the page source directly. Nothing typed here is trusted. It is filtered when you leave
 * the tab, in the preview and again on the server when you save.
 */
export default function HtmlEditor({
    value,
    onChange,
}: {
    value: string;
    onChange: (html: string) => void;
}) {
    const [busy, setBusy] = useState(false);

    const format = async () => {
        setBusy(true);

        try {
            const [prettier, plugin] = await Promise.all([
                import('prettier/standalone'),
                import('prettier/plugins/html'),
            ]);
            const formatted = await prettier.format(sanitizeLegalHtml(value), {
                parser: 'html',
                plugins: [plugin],
                printWidth: 100,
                htmlWhitespaceSensitivity: 'ignore',
            });

            onChange(formatted.trim());
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="overflow-hidden rounded-2xl border border-line bg-white">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-sky-wash px-3 py-2">
                <p className="text-xs text-muted-ink">
                    Edit the HTML directly. Scripts, forms, frames, event handlers and javascript: links are removed.
                </p>
                <div className="flex gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        className="bg-white"
                        onClick={() => onChange(sanitizeLegalHtml(value))}
                    >
                        Remove unsafe code
                    </Button>
                    <Button type="button" size="sm" variant="outline" className="bg-white" disabled={busy} onClick={format}>
                        <WandSparkles className="size-3.5" />
                        Format
                    </Button>
                </div>
            </div>
            {RISKY.test(value) ? (
                <p className="flex items-start gap-2 border-b border-brand/20 bg-brand-soft px-3 py-2 text-xs text-brand-deep">
                    <TriangleAlert className="mt-0.5 size-3.5 shrink-0" />
                    Unsafe code found. It will be removed automatically when you switch tabs or save.
                </p>
            ) : null}
            <CodeMirror
                value={value}
                onChange={onChange}
                extensions={[html({ autoCloseTags: true }), EditorView.lineWrapping]}
                height="26rem"
                basicSetup={{ lineNumbers: true, foldGutter: true, highlightActiveLine: true }}
                aria-label="Page HTML"
            />
        </div>
    );
}
