import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Bookmark, PenLine, Sparkle, Wallet } from 'lucide-react';
import { motion } from 'motion/react';
import { Fragment, useMemo, useState } from 'react';
import {
    DisplayAdSlot,
    useDisplayPlacements,
} from '@/components/ads/display-ad-slot';
import { Reveal } from '@/components/motion/reveal';
import { QuoteCard, type QuoteCardData } from '@/components/quote-card';
import { ShareDialog } from '@/components/share-dialog';
import { Panel, Pill } from '@/components/ui-brand';
import { useAdBreak } from '@/hooks/use-ad-break';
import { cn } from '@/lib/utils';

export default function FeedIndex({
    quotes,
    points,
}: {
    quotes: QuoteCardData[];
    points: { balance: number; lifetime_earned: number };
}) {
    const [category, setCategory] = useState('All');
    const [sharing, setSharing] = useState<QuoteCardData | null>(null);
    const adEvery = useDisplayPlacements('feed_between')[0]?.every_n || 4;
    // The survey opens over this page; when it is confirmed the points refresh in place.
    const adBreak = useAdBreak({
        onComplete: () => router.reload({ only: ['points'] }),
    });

    const categories = useMemo(
        () => ['All', ...Array.from(new Set(quotes.map((q) => q.category)))],
        [quotes],
    );
    const list = quotes.filter(
        (q) => category === 'All' || q.category === category,
    );

    return (
        <>
            <Head title="Inspiration Feed" />

            <div className="px-3.5 pb-3 text-xs text-muted-ink min-[761px]:hidden">
                Home / Inspiration Feed
            </div>

            <motion.section
                className="relative mx-3 mb-4 overflow-hidden rounded-2xl border-l-4 border-brand bg-hero p-5 text-white md:mx-0 md:px-7 md:py-6"
                initial={{ opacity: 0, transform: 'translateY(20px)' }}
                animate={{ opacity: 1, transform: 'translateY(0px)' }}
                transition={{ duration: 0.5, ease: 'easeOut' }}
            >
                <motion.span
                    aria-hidden
                    className="pointer-events-none absolute -top-8 right-4 text-white/10"
                    animate={{
                        transform: [
                            'translateY(0px)',
                            'translateY(10px)',
                            'translateY(0px)',
                        ],
                    }}
                    transition={{
                        duration: 6,
                        repeat: Infinity,
                        ease: 'easeInOut',
                    }}
                >
                    <Sparkle className="size-44 fill-current stroke-0" />
                </motion.span>
                <div className="text-[10px] font-black tracking-[2px] text-brand-glow uppercase">
                    A better thought, a brighter day
                </div>
                <h1 className="my-2 text-[25px] leading-[1.16] font-extrabold min-[361px]:text-[25px] md:text-[28px]">
                    Good words.
                    <br />
                    <em className="text-sky-light not-italic">Bright minds.</em>
                    <br />
                    Real impact.
                </h1>
                <p className="mb-4 max-w-[460px] text-xs text-sky-wash md:text-[13px]">
                    Discover thoughtful, AI-generated inspiration in a simple
                    scrolling feed.
                </p>
                <Link
                    href="/studio"
                    className="inline-flex items-center gap-2 rounded-[10px] bg-brand px-4 py-2.5 text-sm font-black text-white shadow-[0_4px_12px_rgba(0,0,0,0.1)] hover:bg-brand-hover"
                >
                    <Sparkle className="size-4" /> Generate a Quote{' '}
                    <ArrowRight className="size-4" />
                </Link>
            </motion.section>

            <Reveal>
                <Panel className="p-4">
                    <div className="flex items-center gap-3">
                        <span className="grid size-9 shrink-0 place-items-center rounded-full bg-sky-pale text-brand-deep">
                            <Sparkle className="size-4" />
                        </span>
                        <Link
                            href="/studio"
                            className="flex-1 truncate rounded-full bg-sky-pale px-4 py-3 text-left text-sm text-muted-foreground hover:bg-sky-wash"
                        >
                            What inspires you today? Generate a quote...
                        </Link>
                    </div>
                    <div className="mt-4 flex justify-between gap-2 border-t pt-3">
                        {[
                            {
                                href: '/studio',
                                label: 'Generate Quote',
                                icon: PenLine,
                            },
                            {
                                href: '/saved',
                                label: 'Saved Quotes',
                                icon: Bookmark,
                            },
                            {
                                href: '/wallet',
                                label: 'My Wallet',
                                icon: Wallet,
                            },
                        ].map(({ href, label, icon: Icon }) => (
                            <Link
                                key={href}
                                href={href}
                                className="flex flex-1 items-center justify-center gap-1.5 px-0.5 py-2 text-[11px] font-bold text-muted-foreground hover:text-brand-deep min-[361px]:text-[13px]"
                            >
                                <Icon className="size-4 shrink-0" />
                                <span className="truncate">{label}</span>
                            </Link>
                        ))}
                    </div>
                </Panel>
            </Reveal>

            <DisplayAdSlot location="feed_top" />

            <div className="flex flex-wrap items-center justify-between gap-2 px-3.5 pb-3.5 md:px-0.5">
                <div className="flex items-center gap-2">
                    <h2 className="text-[17px] font-extrabold md:text-[19px]">
                        Your inspiration feed
                    </h2>
                    <Pill>{points.balance.toLocaleString()} points</Pill>
                </div>
                <div className="flex max-w-full gap-1.5 overflow-x-auto">
                    {categories.map((c) => (
                        <button
                            key={c}
                            type="button"
                            onClick={() => setCategory(c)}
                            className={cn(
                                'shrink-0 rounded-full border px-3 py-1.5 text-xs font-bold transition-colors max-[360px]:px-2.5',
                                category === c
                                    ? 'border-brand bg-brand text-white'
                                    : 'border-line bg-card text-muted-foreground hover:bg-brand-soft',
                            )}
                        >
                            {c}
                        </button>
                    ))}
                </div>
            </div>

            {list.length === 0 && (
                <p className="px-4 text-sm text-muted-foreground">
                    No published quotes yet.
                </p>
            )}

            {list.map((quote, index) => (
                <Fragment key={quote.slug}>
                    <QuoteCard
                        quote={quote}
                        index={index}
                        onShare={setSharing}
                        onGenerateAd={adBreak.start}
                    />
                    {/* Between quotes only: never after the last one. */}
                    {(index + 1) % adEvery === 0 && index + 1 < list.length ? (
                        <DisplayAdSlot location="feed_between" />
                    ) : null}
                </Fragment>
            ))}

            {adBreak.element}

            <ShareDialog quote={sharing} onClose={() => setSharing(null)} />
        </>
    );
}
