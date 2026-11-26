import { Head, Link } from '@inertiajs/react';
import { Bookmark, Share2, Sparkle } from 'lucide-react';
import { useState } from 'react';
import { DisplayAdSlot } from '@/components/ads/display-ad-slot';
import { Reveal } from '@/components/motion/reveal';
import { QuoteVisual } from '@/components/quote-card';
import { ShareDialog } from '@/components/share-dialog';
import { PageTitle, Panel, Pill } from '@/components/ui-brand';

type SavedQuote = {
    slug: string;
    text: string;
    category: string;
};

export default function SavedIndex({ quotes }: { quotes: SavedQuote[] }) {
    const [sharing, setSharing] = useState<SavedQuote | null>(null);

    return (
        <>
            <Head title="Saved Quotes" />
            <PageTitle
                icon={Bookmark}
                title="Saved Quotes"
                pill={`${quotes.length} saved`}
            />
            <DisplayAdSlot location="saved_top" />
            {quotes.length === 0 && (
                <Reveal>
                    <Panel className="p-4">
                        <p className="text-sm text-muted-foreground">
                            You have not saved a published quote yet.
                        </p>
                        <Link
                            href="/feed"
                            className="mt-3 inline-block rounded-[10px] bg-brand px-4 py-2.5 text-sm font-extrabold text-white hover:bg-brand-hover"
                        >
                            Browse quotes
                        </Link>
                    </Panel>
                </Reveal>
            )}
            {quotes.map((quote, index) => (
                <Reveal key={quote.slug}>
                    <article className="mb-3 overflow-hidden border-y bg-card md:mb-4 md:rounded-2xl md:border md:shadow-[0_6px_25px_rgba(6,23,35,0.08)]">
                        <header className="flex items-center gap-3 px-4 pt-4 pb-3">
                            <span className="grid size-9 place-items-center rounded-full bg-sky-pale text-brand-deep">
                                <Sparkle className="size-4" aria-hidden />
                            </span>
                            <div className="min-w-0 flex-1">
                                <strong className="block text-sm">
                                    Proceeds With Positivity
                                </strong>
                                <small className="text-[11px] text-muted-foreground">
                                    AI-generated · {quote.category}
                                </small>
                            </div>
                            <Pill>Quote</Pill>
                        </header>
                        <div className="pb-4">
                            <QuoteVisual text={quote.text} index={index} />
                        </div>
                        <div className="flex border-t px-3 py-2">
                            <button
                                type="button"
                                onClick={() => setSharing(quote)}
                                className="flex flex-1 items-center justify-center gap-2 rounded-lg px-1 py-2.5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-brand-soft hover:text-brand-deep"
                            >
                                <Share2 className="size-4" /> Share
                            </button>
                        </div>
                    </article>
                </Reveal>
            ))}

            <ShareDialog quote={sharing} onClose={() => setSharing(null)} />
        </>
    );
}
