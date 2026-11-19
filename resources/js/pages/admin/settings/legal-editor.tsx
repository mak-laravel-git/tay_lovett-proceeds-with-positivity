import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, RotateCcw, Save } from 'lucide-react';
import { lazy, Suspense, useEffect, useRef, useState } from 'react';
import { AdminPage, AdminSectionCard } from '@/components/admin/admin-page';
import { LegalPreview } from '@/components/legal/legal-preview';
import { Button } from '@/components/ui/button';
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
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { sanitizeLegalHtml } from '@/lib/legal-html';

// The editors are heavy, so they load only when this page opens.
const RichEditor = lazy(() => import('@/components/legal/rich-editor'));
const HtmlEditor = lazy(() => import('@/components/legal/html-editor'));

type Mode = 'editor' | 'html' | 'preview';

function EditorFallback() {
    return (
        <div className="grid gap-3 rounded-2xl border border-line bg-white p-5">
            <Skeleton className="h-8 w-2/3" />
            <Skeleton className="h-64 w-full" />
        </div>
    );
}

export default function LegalEditor({
    slug,
    title,
    html,
    isCustom,
    updatedAt,
    updatedBy,
}: {
    slug: string;
    title: string;
    html: string;
    isCustom: boolean;
    updatedAt: string | null;
    updatedBy: string | null;
}) {
    const form = useForm({ title, content: html });
    const [mode, setMode] = useState<Mode>('editor');
    const [resetOpen, setResetOpen] = useState(false);
    const [saved, setSaved] = useState({ title, content: html });

    const dirty = form.data.title !== saved.title || form.data.content !== saved.content;
    const dirtyRef = useRef(dirty);
    dirtyRef.current = dirty;
    const [leavePrompt, setLeavePrompt] = useState(false);
    const pendingVisit = useRef<string | URL | null>(null);

    // Warn before leaving with unsaved work: a real tab close/refresh gets the browser's own prompt, and an
    // in-app navigation gets our own dialog. Background prefetch visits (e.g. hovering a sidebar link) are
    // not real navigation and must never trigger either prompt, or it would pop up on almost every mouse move.
    useEffect(() => {
        const beforeUnload = (event: BeforeUnloadEvent) => {
            if (dirtyRef.current) {
                event.preventDefault();
            }
        };

        window.addEventListener('beforeunload', beforeUnload);

        const removeInertia = router.on('before', (event) => {
            const visit = (event as CustomEvent).detail.visit as {
                url: string | URL;
                prefetch?: boolean;
                [key: string]: unknown;
            };

            if (visit.prefetch || !dirtyRef.current) {
                return;
            }

            event.preventDefault();
            pendingVisit.current = visit.url;
            setLeavePrompt(true);
        });

        return () => {
            window.removeEventListener('beforeunload', beforeUnload);
            removeInertia();
        };
    }, []);

    const leaveAnyway = () => {
        setLeavePrompt(false);
        dirtyRef.current = false;

        if (pendingVisit.current) {
            // A plain navigation, not a replayed Inertia visit: it is always correct regardless of what kind
            // of visit (link, form submit, prefetch-turned-real-visit) was originally blocked.
            window.location.assign(String(pendingVisit.current));
        }
    };

    // Every tab change passes the content through the browser filter, so the tabs never hand each other unsafe markup.
    const changeMode = (next: string) => {
        form.setData('content', sanitizeLegalHtml(form.data.content));
        setMode(next as Mode);
    };

    const save = () => {
        const content = sanitizeLegalHtml(form.data.content);

        form.transform((data) => ({ ...data, content }));
        form.put(`/admin/settings/legal/${slug}`, {
            preserveScroll: true,
            onSuccess: () => setSaved({ title: form.data.title, content }),
        });
    };

    return (
        <>
            <Head title={`Edit ${title}`} />
            <AdminPage
                title={`Edit ${title}`}
                description="Changes go live on the public page as soon as you publish. Unsafe code is always removed."
                actions={
                    <>
                        <Button asChild variant="outline">
                            <Link href="/admin/settings?tab=legal">
                                <ArrowLeft className="size-4" />
                                Back
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <a href={`/${slug}`} target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="size-4" />
                                View page
                            </a>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setResetOpen(true)}
                            disabled={!isCustom}
                        >
                            <RotateCcw className="size-4" />
                            Reset to default
                        </Button>
                        <Button
                            type="button"
                            onClick={save}
                            disabled={form.processing || !dirty}
                            className="bg-[#ed2948] text-white hover:bg-[#d61f3d]"
                        >
                            {form.processing ? <Spinner /> : <Save className="size-4" />}
                            Save & publish
                        </Button>
                    </>
                }
            >
                <AdminSectionCard
                    title="Page"
                    description={
                        isCustom && updatedAt
                            ? `Published ${new Date(updatedAt).toLocaleString()}${updatedBy ? ` by ${updatedBy}` : ''}.`
                            : 'Showing the built-in starter text. Publish to replace it.'
                    }
                >
                    <div className="grid max-w-md gap-2">
                        <Label htmlFor="legal-title">Page title</Label>
                        <Input
                            id="legal-title"
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                            className="bg-white"
                        />
                        {form.errors.title ? <p className="text-sm text-brand-deep">{form.errors.title}</p> : null}
                    </div>
                </AdminSectionCard>

                <Tabs value={mode} onValueChange={changeMode} className="gap-3">
                    <TabsList>
                        <TabsTrigger value="editor" className="px-4">Editor</TabsTrigger>
                        <TabsTrigger value="html" className="px-4">HTML</TabsTrigger>
                        <TabsTrigger value="preview" className="px-4">Preview</TabsTrigger>
                    </TabsList>

                    <TabsContent value="editor">
                        <Suspense fallback={<EditorFallback />}>
                            <RichEditor value={form.data.content} onChange={(content) => form.setData('content', content)} />
                        </Suspense>
                    </TabsContent>
                    <TabsContent value="html">
                        <Suspense fallback={<EditorFallback />}>
                            <HtmlEditor value={form.data.content} onChange={(content) => form.setData('content', content)} />
                        </Suspense>
                    </TabsContent>
                    <TabsContent value="preview">
                        <LegalPreview title={form.data.title} html={form.data.content} />
                    </TabsContent>
                </Tabs>

                {form.errors.content ? (
                    <p role="alert" className="text-sm font-medium text-brand-deep">
                        {form.errors.content}
                    </p>
                ) : null}
            </AdminPage>

            <Dialog open={resetOpen} onOpenChange={setResetOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Reset to the default text?</DialogTitle>
                        <DialogDescription>
                            The published page goes back to the built-in starter text. Your current text is lost.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setResetOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() => {
                                setSaved({ title: form.data.title, content: form.data.content });
                                router.post(`/admin/settings/legal/${slug}/reset`, {}, { onSuccess: () => setResetOpen(false) });
                            }}
                        >
                            Reset page
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={leavePrompt} onOpenChange={(open) => !open && setLeavePrompt(false)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Leave without saving?</DialogTitle>
                        <DialogDescription>
                            You have changes that have not been published. They will be lost if you leave now.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setLeavePrompt(false)}>
                            Keep editing
                        </Button>
                        <Button type="button" variant="destructive" onClick={leaveAnyway}>
                            Leave this page
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

LegalEditor.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Application settings', href: '/admin/settings?tab=legal' },
        { title: 'Legal page', href: '#' },
    ],
};
