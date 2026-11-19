import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Copy, Eye, Send, Share2, Sparkle } from 'lucide-react';
import { motion } from 'motion/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Reveal } from '@/components/motion/reveal';
import { QuoteVisual } from '@/components/quote-card';
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
import { cn } from '@/lib/utils';

type Category = { id: number; name: string };

type StudioQuote = {
    slug: string;
    text: string;
    category: string;
    status: 'approved' | 'published';
};

export default function Studio({
    categories,
    quotes,
}: {
    categories: Category[];
    quotes: StudioQuote[];
}) {
    const { site } = usePage().props;
    const [, copy] = useClipboard();
    const [categoryId, setCategoryId] = useState(
        categories[0] ? String(categories[0].id) : '',
    );
    const [sharing, setSharing] = useState<StudioQuote | null>(null);

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
                        Generated quotes are approved automatically. Publish
                        one when you want it on the feed.
                    </p>
                    <Form
                        action="/studio"
                        method="post"
                        className="mt-5 grid gap-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="quote_category_id">
                                        Inspiration category
                                    </Label>
                                    <input
                                        type="hidden"
                                        name="quote_category_id"
                                        value={categoryId}
                                    />
                                    <Select
                                        value={categoryId}
                                        onValueChange={setCategoryId}
                                        disabled={
                                            processing ||
                                            categories.length === 0
                                        }
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
                                {errors.generation && (
                                    <p className="text-sm text-brand-deep">
                                        {errors.generation}
                                    </p>
                                )}
                                {processing ? (
                                    <p className="text-sm text-muted-foreground">
                                        Generating your quote…
                                    </p>
                                ) : null}
                                <Button
                                    className="h-11 w-fit rounded-[10px] px-5 font-black"
                                    disabled={
                                        processing || categories.length === 0
                                    }
                                    type="submit"
                                >
                                    {processing ? (
                                        <Spinner />
                                    ) : (
                                        <Sparkle />
                                    )}
                                    {processing
                                        ? 'Generating…'
                                        : 'Create quote'}
                                </Button>
                            </>
                        )}
                    </Form>
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

            <div className="px-3 min-[761px]:px-0">
                <Notice>
                    After reading a quote in the feed, use Generate Ad to open
                    the full-screen advertisement. No earnings are generated
                    until a compliant provider is connected.
                </Notice>
            </div>

            <ShareDialog quote={sharing} onClose={() => setSharing(null)} />
        </>
    );
}
