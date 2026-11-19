import { Link, usePage } from '@inertiajs/react';
import { Gem, MessageSquareQuote, Wallet } from 'lucide-react';
import { motion } from 'motion/react';
import { BrandMark } from '@/components/brand-mark';
import { FadeIn } from '@/components/motion/fade-in';
import { MotionProvider } from '@/components/motion/reveal';
import { cn } from '@/lib/utils';
import type { AuthLayoutProps } from '@/types';

const tags = [
    { label: 'Inspirational quote feed', icon: MessageSquareQuote },
    { label: 'Ad previews', icon: Gem },
    { label: 'Wallet overview', icon: Wallet },
];

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;
    const url = usePage().url;
    const registering = url.startsWith('/register');
    const showTabs = registering || url.startsWith('/login');

    return (
        <MotionProvider>
            <div className="flex min-h-dvh flex-col items-center justify-center gap-3.5 bg-night-1 bg-auth-stage px-3.5 py-8 min-[761px]:px-[18px]">
                <motion.div
                    className="grid w-full max-w-[470px] overflow-hidden rounded-[19px] border border-night-edge bg-night-2 shadow-[0_28px_90px_rgba(0,0,0,0.4)] min-[761px]:max-w-[1060px] min-[761px]:grid-cols-[1.04fr_0.96fr] min-[761px]:rounded-[26px]"
                    initial={{ opacity: 0, transform: 'translateY(24px)' }}
                    animate={{ opacity: 1, transform: 'translateY(0px)' }}
                    transition={{ duration: 0.5, ease: 'easeOut' }}
                >
                    <div className="flex flex-col justify-between gap-5 border-b-[3px] border-brand bg-auth-intro p-6 pb-5 text-white max-[360px]:p-[18px] min-[761px]:gap-14 min-[761px]:border-b-4 min-[761px]:p-[55px_44px]">
                        <BrandMark
                            name={String(name).toUpperCase()}
                            tagline={false}
                        />
                        <div>
                            <h1 className="mb-2 text-[25px] leading-[1.12] font-black tracking-[-0.7px] max-[360px]:text-[23px] min-[761px]:mb-[18px] min-[761px]:text-[clamp(30px,4vw,48px)] min-[761px]:tracking-[-1.4px]">
                                Find inspiration.
                                <br />
                                <span className="text-brand-glow">
                                    Share brighter moments.
                                </span>
                            </h1>
                            <p className="max-w-[390px] text-xs text-mist-soft min-[761px]:text-base">
                                Explore encouraging AI-powered quotes, discover
                                positive ideas, and see how the platform could
                                work.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-1.5 min-[761px]:gap-2.5">
                            {tags.map(({ label, icon: Icon }, i) => (
                                <motion.span
                                    key={label}
                                    initial={{
                                        opacity: 0,
                                        transform: 'translateY(10px)',
                                    }}
                                    animate={{
                                        opacity: 1,
                                        transform: 'translateY(0px)',
                                    }}
                                    transition={{ delay: 0.3 + i * 0.1 }}
                                    className={cn(
                                        'inline-flex items-center gap-1.5 rounded-full border px-2 py-1 text-[10px] text-sky-wash min-[761px]:px-3 min-[761px]:py-[7px] min-[761px]:text-xs',
                                        i === tags.length - 1
                                            ? 'border-brand bg-wine text-white'
                                            : 'border-steel bg-night-3',
                                    )}
                                >
                                    <Icon className="size-3" />
                                    {label}
                                </motion.span>
                            ))}
                        </div>
                    </div>

                    <div className="theme-light text-ink max-[360px]:p-[19px] bg-white p-[25px_22px] min-[761px]:p-[44px_42px]">
                        <FadeIn>
                            <h2 className="text-[23px] font-bold min-[761px]:text-[27px]">
                                {title}
                            </h2>
                            <p className="mt-1.5 mb-5 text-[13px] text-muted-ink">
                                {description}
                            </p>
                            {showTabs && (
                                <div
                                    role="tablist"
                                    className="mb-5 flex gap-1.5 rounded-xl bg-sky-pale p-[5px]"
                                >
                                    {[
                                        {
                                            href: '/login',
                                            label: 'Log in',
                                            on: !registering,
                                        },
                                        {
                                            href: '/register',
                                            label: 'Create account',
                                            on: registering,
                                        },
                                    ].map((tab) => (
                                        <Link
                                            key={tab.href}
                                            href={tab.href}
                                            role="tab"
                                            aria-selected={tab.on}
                                            className={cn(
                                                'w-1/2 rounded-[9px] py-2.5 text-center text-sm font-extrabold transition-colors',
                                                tab.on
                                                    ? 'bg-brand text-white'
                                                    : 'text-muted-ink hover:bg-white/70',
                                            )}
                                        >
                                            {tab.label}
                                        </Link>
                                    ))}
                                </div>
                            )}
                            {children}
                        </FadeIn>
                    </div>
                </motion.div>

                <p className="text-center text-[13px] text-mist-dim">
                    <Link className="text-sky underline" href="/privacy">
                        Privacy Policy
                    </Link>
                    {' · '}
                    <Link className="text-sky underline" href="/terms">
                        Terms of Service
                    </Link>
                </p>
            </div>
        </MotionProvider>
    );
}
