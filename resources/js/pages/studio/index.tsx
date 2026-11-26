import { Form, Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Copy, Eye, Send, Share2, Sparkle } from 'lucide-react';
import { motion } from 'motion/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { DisplayAdSlot } from '@/components/ads/display-ad-slot';
import { Reveal } from '@/components/motion/reveal';
import { QuoteVisual } from '@/components/quote-card';
import { WorkflowPanel } from '@/components/quote-workflow/workflow-panel';
import { ShareDialog } from '@/components/share-dialog';
import { Notice, PageTitle, Panel, Pill } from '@/components/ui-brand';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useClipboard } from '@/hooks/use-clipboard';
import { startWorkflow } from '@/lib/workflow-api';
import type { Workflow } from '@/lib/workflow-api';
import { cn } from '@/lib/utils';

type Category = { id: number; name: string };

type StudioQuote = {
    slug: string;
    text: string;
    category: string;
    status: 'approved' | 'published';
};

type WorkflowInfo = {
    /** The step-by-step flow; when off, one press creates a quote. */
    enabled: boolean;
    active: Workflow | null;
    disclosure: string;
};

export default function Studio({
    categories,
    quotes,
    workflow,
}: {
    categories: Category[];
    quotes: StudioQuote[];
    workflow: WorkflowInfo;
}) {
    const { site } = usePage().props;
    const [, copy] = useClipboard();
    const form = useForm({
        quote_category_id: categories[0] ? String(categories[0].id) : '',
    });
    const [run, setRun] = useState<Workflow | null>(workflow.active);
    const [starting, setStarting] = useState(false);
    const [startError, setStartError] = useState<string | null>(null);
    const busy = form.processing || starting;
    // The server reports these as validation errors, which are not fields of the form.
    const errors = form.errors as Record<string, string | undefined>;
    const [sharing, setSharing] = useState<StudioQuote | null>(null);
    const completed = run?.state === 'completed';

    // The finished quote belongs in the list below.
    useEffect(() => {
        if (completed) {
            router.reload({ only: ['quotes'] });
        }
    }, [completed]);

    const create = async () => {
        if (!workflow.enabled) {
            form.post('/studio', { preserveScroll: true });

            return;
        }

        setStarting(true);
        setStartError(null);

        try {
            setRun(
                (await startWorkflow(Number(form.data.quote_category_id)))
                    .workflow,
            );
        } catch (caught) {
            setStartError(
                caught instanceof Error
                    ? caught.message
                    : 'Could not start. Please try again.',
            );
        } finally {
            setStarting(false);
        }
    };

    return (
        <>
            <Head title="Quote Studio" />
            <PageTitle
                icon={Sparkle}
                title="AI Quote Studio"
                pill={`${quotes.length} quote${quotes.length === 1 ? '' : 's'}`}
            />
            <Reveal>
                <Panel className="p-4">
                    <p className="text-sm text-muted-foreground">
                        {workflow.enabled
                            ? workflow.disclosure
                            : 'Generated quotes are approved automatically. Publish one when you want it on the feed.'}
                    </p>
                    {run ? (
                        <WorkflowPanel
                            workflow={run}
                            onChange={setRun}
                            onClose={() => {
                                setRun(null);
                                router.reload();
                            }}
                        />
                    ) : (
                        <form
                            className="mt-5 grid gap-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                void create();
                            }}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="quote_category_id">
                                    Inspiration category
                                </Label>
                                <Select
                                    value={form.data.quote_category_id}
                                    onValueChange={(value) =>
                                        form.setData('quote_category_id', value)
                                    }
                                    disabled={busy || categories.length === 0}
                                >
                                    <SelectTrigger
                                        id="quote_category_id"
                                        className="w-full bg-white"
                                    >
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((category) => (
                                            <SelectItem
                                                key={category.id}
                                                value={String(category.id)}
                                            >
                                                {category.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {[startError, errors.workflow, errors.generation]
                                .filter(Boolean)
                                .map((message) => (
                                    <p
                                        key={message}
                                        className="text-sm text-brand-deep"
                                        role="alert"
                                    >
                                        {message}
                                    </p>
                                ))}
                            {form.processing ? (
                                <p className="text-sm text-muted-foreground">
                                    Generating your quote…
                                </p>
                            ) : null}
                            <Button
                                className="h-11 w-fit rounded-[10px] px-5 font-black"
                                disabled={busy || categories.length === 0}
                                type="submit"
                            >
                                {busy ? <Spinner /> : <Sparkle />}
                                {form.processing
                                    ? 'Generating…'
                                    : starting
                                      ? 'Starting…'
                                      : 'Create quote'}
                            </Button>
                        </form>
                    )}
                </Panel>
            </Reveal>

            <div className="mt-4 flex items-center justify-between gap-2 px-3.5 pb-3.5 md:px-0.5">
                <h2 className="text-[17px] font-extrabold md:text-[19px]">
                    Your quotes
                </h2>
            </div>

            {quotes.length === 0 ? (
                <p className="px-4 text-sm text-muted-foreground">
                    No quotes yet. Create one to get started.
                </p>
            ) : (
                quotes.map((quote, index) => (
                    <Reveal key={quote.slug}>
                        <article className="mb-3 overflow-hidden border-y bg-card md:mb-4 md:rounded-2xl md:border md:shadow-[0_6px_25px_rgba(6,23,35,0.08)]">
                            <header className="flex items-center gap-3 px-4 pt-4 pb-3">
                                <span className="grid size-9 place-items-center rounded-full bg-sky-pale text-brand-deep">
                                    <Sparkle className="size-4" aria-hidden />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <strong className="block text-sm">
                                        {site.name}
                                    </strong>
                                    <small className="text-[11px] text-muted-foreground">
                                        AI-generated · {quote.category}
                                    </small>
                                </div>
                                <Pill
                                    className={cn(
                                        quote.status === 'published' &&
                                            'bg-[#e7f7ff] text-[#0f6f8f]',
                                    )}
                                >
                                    {quote.status === 'published'
                                        ? 'Published'
                                        : 'Approved'}
                                </Pill>
                            </header>

                            <QuoteVisual text={quote.text} index={index} />

                            <div className="flex items-center justify-between gap-1.5 px-3 py-2">
                                <button
                                    type="button"
                                    onClick={async () => {
                                        if (await copy(quote.text)) {
                                            toast.success('Quote copied');
                                        }
                                    }}
                                    className="flex flex-1 items-center justify-center gap-2 rounded-lg px-1 py-2.5 text-xs font-bold text-muted-foreground transition-colors hover:bg-brand-soft hover:text-brand-deep min-[361px]:text-[13px]"
                                >
                                    <Copy className="size-4" /> Copy
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setSharing(quote)}
                                    className="flex flex-1 items-center justify-center gap-2 rounded-lg px-1 py-2.5 text-xs font-bold text-muted-foreground transition-colors hover:bg-brand-soft hover:text-brand-deep min-[361px]:text-[13px]"
                                >
                                    <Share2 className="size-4" /> Share
                                </button>
                            </div>

                            <footer className="flex flex-wrap items-center justify-between gap-2.5 border-t-2 border-brand-soft px-4 py-3">
                                <span className="text-xs text-muted-foreground">
                                    {quote.status === 'approved'
                                        ? 'Ready to publish to the feed'
                                        : 'Live on the inspiration feed'}
                                </span>
                                {quote.status === 'approved' ? (
                                    <Form
                                        action={`/studio/quotes/${quote.slug}/publish`}
                                        method="post"
                                    >
                                        {({ processing }) => (
                                            <motion.button
                                                type="submit"
                                                disabled={processing}
                                                whileTap={{ scale: 0.96 }}
                                                className="inline-flex min-w-[140px] items-center justify-center gap-2 rounded-[10px] bg-brand px-4 py-2.5 text-sm font-extrabold text-white shadow-[0_5px_15px_color-mix(in_srgb,var(--color-brand)_20%,transparent)] hover:bg-brand-hover disabled:opacity-70"
                                            >
                                                {processing ? (
                                                    <Spinner />
                                                ) : (
                                                    <Send className="size-4" />
                                                )}
                                                Publish
                                            </motion.button>
                                        )}
                                    </Form>
                                ) : (
                                    <Link
                                        href={`/q/${quote.slug}`}
                                        className="inline-flex min-w-[140px] items-center justify-center gap-2 rounded-[10px] bg-brand px-4 py-2.5 text-sm font-extrabold text-white shadow-[0_5px_15px_color-mix(in_srgb,var(--color-brand)_20%,transparent)] hover:bg-brand-hover"
                                    >
                                        <Eye className="size-4" /> View
                                    </Link>
                                )}
                            </footer>
                        </article>
                    </Reveal>
                ))
            )}

            {workflow.enabled ? (
                <div className="px-3 min-[761px]:px-0">
                    <Notice>
                        Surveys are confirmed by the survey provider, never by
                        this page, and your results are kept on My Earnings and
                        your profile.
                    </Notice>
                </div>
            ) : null}

            <DisplayAdSlot location="studio_below" />

            <ShareDialog quote={sharing} onClose={() => setSharing(null)} />
        </>
    );
}
