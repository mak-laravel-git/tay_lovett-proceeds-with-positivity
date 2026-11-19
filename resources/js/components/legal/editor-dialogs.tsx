import type { Editor } from '@tiptap/react';
import { Loader2, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { xsrfToken } from '@/lib/csrf';

/**
 * Turn what an administrator typed into an address the page may link to, or null when it is not allowed.
 * Only web, email, phone and in-page addresses pass, so a javascript: link never reaches the document.
 */
export function normalizeLink(input: string): string | null {
    const value = input.trim();

    if (value === '') {
        return null;
    }

    if (/^(https?:\/\/|mailto:|tel:|#)/i.test(value)) {
        return value;
    }

    if (/^[a-z][a-z0-9+.-]*:/i.test(value)) {
        return null;
    }

    if (value.startsWith('/') && !value.startsWith('//')) {
        return value;
    }

    return /^[^\s/]+\.[^\s/]{2,}/.test(value) ? `https://${value}` : null;
}

export function LinkDialog({
    editor,
    open,
    onOpenChange,
}: {
    editor: Editor;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [url, setUrl] = useState('');
    const [newTab, setNewTab] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        if (open) {
            const attributes = editor.getAttributes('link');
            setUrl((attributes.href as string | undefined) ?? '');
            setNewTab(attributes.target !== '_self');
            setError('');
        }
    }, [open, editor]);

    const apply = () => {
        const href = normalizeLink(url);

        if (!href) {
            setError('Enter a web address (https://...), an email (mailto:...), a phone (tel:...) or a #section.');

            return;
        }

        editor
            .chain()
            .focus()
            .extendMarkRange('link')
            .setLink({ href, target: newTab ? '_blank' : null })
            .run();
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Link</DialogTitle>
                    <DialogDescription>
                        Select some text first, or the link text is the address.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-3">
                    <div className="grid gap-2">
                        <Label htmlFor="link-url">Address</Label>
                        <Input
                            id="link-url"
                            value={url}
                            onChange={(event) => {
                                setUrl(event.target.value);
                                setError('');
                            }}
                            onKeyDown={(event) => event.key === 'Enter' && apply()}
                            placeholder="https://example.com"
                            autoFocus
                        />
                        {error ? <p className="text-sm text-brand-deep">{error}</p> : null}
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox checked={newTab} onCheckedChange={(checked) => setNewTab(checked === true)} />
                        Open in a new tab
                    </label>
                </div>
                <DialogFooter>
                    {editor.isActive('link') ? (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                editor.chain().focus().extendMarkRange('link').unsetLink().run();
                                onOpenChange(false);
                            }}
                        >
                            Remove link
                        </Button>
                    ) : null}
                    <Button type="button" onClick={apply}>
                        Apply
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export function ImageDialog({
    editor,
    open,
    onOpenChange,
}: {
    editor: Editor;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [url, setUrl] = useState('');
    const [alt, setAlt] = useState('');
    const [error, setError] = useState('');
    const [uploading, setUploading] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (open) {
            setUrl('');
            setAlt('');
            setError('');
        }
    }, [open]);

    const insert = (src: string) => {
        editor.chain().focus().setImage({ src, alt }).run();
        onOpenChange(false);
    };

    const upload = async (file: File) => {
        setUploading(true);
        setError('');

        try {
            const body = new FormData();
            body.append('image', file);

            const response = await fetch('/admin/legal/images', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const data = await response.json();

            if (!response.ok) {
                setError(data?.errors?.image?.[0] ?? data?.message ?? 'The upload failed.');

                return;
            }

            insert(data.url);
        } catch {
            setError('The upload failed. Check your connection.');
        } finally {
            setUploading(false);
        }
    };

    const addByUrl = () => {
        const value = url.trim();

        if (!/^https:\/\//i.test(value) && !value.startsWith('/storage/')) {
            setError('Use a secure https:// address, or upload a file.');

            return;
        }

        insert(value);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Image</DialogTitle>
                    <DialogDescription>
                        Upload a PNG, JPG or WebP up to 2 MB, or paste a secure image address.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-3">
                    <input
                        ref={fileInput}
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        className="hidden"
                        onChange={(event) => {
                            const file = event.target.files?.[0];

                            if (file) {
                                void upload(file);
                            }

                            event.target.value = '';
                        }}
                    />
                    <Button
                        type="button"
                        variant="outline"
                        disabled={uploading}
                        onClick={() => fileInput.current?.click()}
                    >
                        {uploading ? <Loader2 className="size-4 animate-spin" /> : <Upload className="size-4" />}
                        Upload from your computer
                    </Button>
                    <div className="grid gap-2">
                        <Label htmlFor="image-url">Or image address</Label>
                        <Input
                            id="image-url"
                            value={url}
                            onChange={(event) => {
                                setUrl(event.target.value);
                                setError('');
                            }}
                            placeholder="https://example.com/photo.png"
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="image-alt">Description (for screen readers)</Label>
                        <Input id="image-alt" value={alt} onChange={(event) => setAlt(event.target.value)} />
                    </div>
                    {error ? <p className="text-sm text-brand-deep">{error}</p> : null}
                </div>
                <DialogFooter>
                    <Button type="button" onClick={addByUrl} disabled={url.trim() === ''}>
                        Insert image
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
