import { router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Copy,
    Heart,
    Megaphone,
    Share2,
    Sparkle,
} from 'lucide-react';
import { motion } from 'motion/react';
import { toast } from 'sonner';
import { Reveal } from '@/components/motion/reveal';
import { Pill } from '@/components/ui-brand';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';

export type QuoteCardData = {
    slug: string;
    text: string;
    category: string;
    saved: boolean;
};

const skins = [
    'bg-quote-dark text-white',
    'bg-quote-light text-ink',
    'bg-quote-rose text-white',
] as const;

export function QuoteVisual({ text, index }: { text: string; index: number }) {
    const skin = skins[index % skins.length];
    const light = skin.includes('text-ink');

    return (
        <div
            className={cn(
                'relative mx-3 flex min-h-[225px] flex-col items-center justify-center rounded-xl border-b-4 border-brand px-4 py-7 text-center md:mx-4 md:min-h-[220px] md:px-6',
                skin,
            )}
        >
            <span
                aria-hidden
                className={cn(
                    'absolute top-0 left-6 font-serif text-6xl opacity-80',
                    light ? 'text-brand' : 'text-sky-light',
                )}
            >
                “
            </span>
            <p className="my-4 max-w-[520px] text-[22px] leading-[1.4] font-bold tracking-tight md:text-2xl">
                {text}
            </p>
            <small
                className={cn(
                    'text-[11px] font-extrabold tracking-[1.5px] uppercase',
                    light ? 'text-ink/70' : 'text-sky-light',
                )}
            >
                Proceeds With Positivity
            </small>
        </div>
    );
}

export function QuoteCard({
    quote,
    index,
    onShare,
}: {
    quote: QuoteCardData;
    index: number;
    onShare: (quote: QuoteCardData) => void;
}) {
    const { site } = usePage().props;
    const [, copy] = useClipboard();

    return (
        <Reveal>
            <article className="mb-3 overflow-hidden border-y bg-card md:mb-4 md:rounded-2xl md:border md:shadow-[0_6px_25px_rgba(6,23,35,0.08)]">
                <header className="flex items-center gap-3 px-4 pt-4 pb-3">
                    <span className="grid size-9 place-items-center rounded-full bg-sky-pale text-brand-deep">
                        <Sparkle className="size-4" aria-hidden />
                    </span>
                    <div className="min-w-0 flex-1">
                        <strong className="block text-sm">{site.name}</strong>
                        <small className="text-[11px] text-muted-foreground">
                            AI-generated · {quote.category}
                        </small>
                    </div>
                    <Pill>Quote</Pill>
                </header>

                <QuoteVisual text={quote.text} index={index} />

                <div className="flex items-center justify-between gap-1.5 px-3 py-2">
                    <ActionButton
                        onClick={() =>
                            router.post(
                                `/quotes/${quote.slug}/save`,
                                {},
                                { preserveScroll: true },
                            )
                        }
                        disabled={quote.saved}
                    >
                        <Heart
                            className={cn(
                                'size-4',
                                quote.saved && 'fill-brand text-brand',
                            )}
                        />
                        {quote.saved ? 'Saved' : 'Save'}
                    </ActionButton>
                    <ActionButton
                        onClick={async () => {
                            if (await copy(quote.text)) {
                                toast.success('Quote copied');
                            }
                        }}
                    >
                        <Copy className="size-4" /> Copy
                    </ActionButton>
                    <ActionButton onClick={() => onShare(quote)}>
                        <Share2 className="size-4" /> Share
                    </ActionButton>
                </div>

                <footer className="flex flex-wrap items-center justify-between gap-2.5 border-t-2 border-brand-soft px-4 py-3">
                    <span className="text-xs text-muted-foreground">
                        After reading this quote, tap Generate Ad
                    </span>
                    <motion.button
                        type="button"
                        whileTap={{ scale: 0.96 }}
                        // Opens the real survey flow; points are awarded only after the provider confirms.
                        onClick={() => router.visit('/surveys')}
                        className="inline-flex min-w-[140px] items-center justify-center gap-2 rounded-[10px] bg-brand px-4 py-2.5 text-sm font-extrabold text-white shadow-[0_5px_15px_color-mix(in_srgb,var(--color-brand)_20%,transparent)] hover:bg-brand-hover"
                    >
                        <Megaphone className="size-4" /> Generate Ad{' '}
                        <ArrowRight className="size-4" />
                    </motion.button>
                </footer>
            </article>
        </Reveal>
    );
}

function ActionButton({
    children,
    ...props
}: React.ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            type="button"
            className="flex flex-1 items-center justify-center gap-2 rounded-lg px-1 py-2.5 text-xs font-bold text-muted-foreground transition-colors hover:bg-brand-soft hover:text-brand-deep disabled:pointer-events-none min-[361px]:text-[13px]"
            {...props}
        >
            {children}
        </button>
    );
}
