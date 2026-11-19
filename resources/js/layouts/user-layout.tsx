import { Link, usePage } from '@inertiajs/react';
import {
    ArrowLeftRight,
    Bookmark,
    ChevronDown,
    CircleDollarSign,
    ClipboardList,
    House,
    type LucideIcon,
    Megaphone,
    Menu,
    PenLine,
    ShieldCheck,
    Sparkle,
    UserRound,
    Wallet,
} from 'lucide-react';
import { motion } from 'motion/react';
import { BrandMark } from '@/components/brand-mark';
import {
    MotionProvider,
    Stagger,
    StaggerItem,
} from '@/components/motion/reveal';
import { PageFade } from '@/components/motion/page-fade';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { UserMenuContent } from '@/components/user-menu-content';
import { cn } from '@/lib/utils';

type UserNavItem = {
    title: string;
    short: string;
    href: string;
    icon: LucideIcon;
};

const items: UserNavItem[] = [
    { title: 'Inspiration Feed', short: 'Feed', href: '/feed', icon: House },
    { title: 'Quote Studio', short: 'Create', href: '/studio', icon: PenLine },
    { title: 'Surveys', short: 'Surveys', href: '/surveys', icon: ClipboardList },
    { title: 'My Wallet', short: 'Wallet', href: '/wallet', icon: Wallet },
    {
        title: 'My Earnings',
        short: 'Earnings',
        href: '/earnings',
        icon: CircleDollarSign,
    },
    { title: 'Saved Quotes', short: 'Saved', href: '/saved', icon: Bookmark },
    {
        title: 'Withdrawals',
        short: 'Withdraw',
        href: '/withdrawals',
        icon: ArrowLeftRight,
    },
];

// Bottom bar: Feed, Create, Surveys, Saved. Everything else lives under "More".
const mobileItems = [items[0], items[1], items[2], items[5]];
const moreItems = [items[3], items[4], items[6]];

export default function UserLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { name, auth } = usePage().props;
    const current = usePage().url;
    const isActive = (href: string) => current.startsWith(href);

    return (
        <MotionProvider>
            <div className="min-h-dvh bg-background text-foreground">
                <header className="sticky top-0 z-20 flex h-[59px] items-center justify-between gap-3.5 border-b-[3px] border-brand bg-ink px-4 text-white md:h-[66px] md:px-8">
                    <BrandMark name={String(name)} />
                    {auth.user && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    className="rounded-full border border-ink-line bg-ink-soft text-sky-light hover:bg-ink-line"
                                >
                                    {auth.user.name.split(' ')[0]}
                                    <ChevronDown />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </header>

                <div className="mx-auto grid w-full max-w-[520px] items-start gap-[26px] pt-3.5 pb-24 min-[761px]:max-w-[1020px] min-[761px]:grid-cols-[210px_minmax(0,1fr)] min-[761px]:px-6 min-[761px]:pt-6 min-[761px]:pb-20 min-[1191px]:max-w-[1480px] min-[1191px]:grid-cols-[248px_minmax(0,690px)_286px]">
                    <aside className="sticky top-[90px] hidden min-[761px]:block">
                        <nav
                            aria-label="Primary"
                            className="rounded-2xl border bg-card p-3.5 shadow-[0_6px_25px_rgba(6,23,35,0.08)]"
                        >
                            <div className="mx-3 mt-1 mb-2 text-[10px] font-extrabold tracking-[1.7px] text-muted-foreground uppercase">
                                User workspace
                            </div>
                            {items.map((item) => (
                                <SideLink
                                    key={item.href}
                                    item={item}
                                    active={isActive(item.href)}
                                />
                            ))}
                        </nav>
                        <div className="mt-4 rounded-2xl bg-gradient-to-br from-ink via-night-warm to-brand-deep p-4 text-white">
                            <b className="flex items-center gap-2 text-sm">
                                <Sparkle className="size-4 text-sky-light" />
                                A little positivity goes a long way.
                            </b>
                            <p className="mt-1.5 text-xs text-sky-wash/80">
                                Complete partner surveys to earn points. Points
                                are added only after the survey provider
                                confirms your completion.
                            </p>
                        </div>
                    </aside>

                    <main className="min-w-0">
                        <PageFade>{children}</PageFade>
                    </main>

                    <aside className="sticky top-[90px] hidden min-[1191px]:block">
                        <Stagger className="grid gap-4">
                            <StaggerItem>
                                <RailCard title="Advertisement preview">
                                    <div className="rounded-xl border-2 border-dashed border-brand/50 bg-brand-soft/60 p-4 text-center text-muted-foreground">
                                        <span className="inline-block rounded-full bg-brand-soft px-2.5 py-1 text-[10px] font-extrabold text-brand-deep">
                                            Sponsored
                                        </span>
                                        <b className="my-1.5 flex items-center justify-center gap-2 text-foreground">
                                            <Megaphone className="size-4 text-brand" />
                                            Ad placement area
                                        </b>
                                        <p className="text-xs">
                                            Approved advertising provider will
                                            supply real ads.
                                        </p>
                                    </div>
                                </RailCard>
                            </StaggerItem>
                            <StaggerItem>
                                <RailCard title="Platform purpose">
                                    <p className="text-xs text-muted-foreground">
                                        Explore positive quotes, view in-website
                                        advertisements, and follow your eligible
                                        earnings once a compliant provider is
                                        connected.
                                    </p>
                                </RailCard>
                            </StaggerItem>
                        </Stagger>
                    </aside>
                </div>

                <nav
                    aria-label="Mobile"
                    className="fixed inset-x-0 bottom-0 z-30 flex h-[calc(62px+env(safe-area-inset-bottom))] items-stretch justify-around border-t-[3px] border-brand bg-ink pb-[env(safe-area-inset-bottom)] text-mist shadow-[0_-5px_22px_rgba(0,26,49,0.05)] min-[761px]:hidden"
                >
                    {mobileItems.map((item) => {
                        const active = isActive(item.href);

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'relative flex w-1/5 flex-col items-center justify-center gap-0.5 text-[10px] font-extrabold',
                                    active && 'text-sky',
                                )}
                            >
                                {active && (
                                    <motion.span
                                        layoutId="mobile-nav-indicator"
                                        className="absolute top-0 h-0.5 w-8 rounded-full bg-sky"
                                    />
                                )}
                                <item.icon className="size-[22px]" />
                                {item.short}
                            </Link>
                        );
                    })}
                    <Sheet>
                        <SheetTrigger className="flex w-1/5 flex-col items-center justify-center gap-0.5 text-[10px] font-extrabold">
                            <Menu className="size-[22px]" />
                            More
                        </SheetTrigger>
                        <SheetContent side="bottom">
                            <SheetHeader>
                                <SheetTitle>More</SheetTitle>
                            </SheetHeader>
                            <div className="grid gap-1 p-4 pb-8">
                                {moreItems.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold hover:bg-brand-soft"
                                    >
                                        <item.icon className="size-4" />
                                        {item.title}
                                    </Link>
                                ))}
                                <Link
                                    href="/settings/profile"
                                    className="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold hover:bg-brand-soft"
                                >
                                    <UserRound className="size-4" />
                                    Profile
                                </Link>
                                <Link
                                    href="/settings/security"
                                    className="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold hover:bg-brand-soft"
                                >
                                    <ShieldCheck className="size-4" />
                                    Security
                                </Link>
                            </div>
                        </SheetContent>
                    </Sheet>
                </nav>
            </div>
        </MotionProvider>
    );
}

function SideLink({ item, active }: { item: UserNavItem; active: boolean }) {
    return (
        <Link
            href={item.href}
            className={cn(
                'my-0.5 flex items-center gap-3 rounded-xl px-3 py-[13px] text-[15px] leading-[30px] font-bold transition-colors',
                active
                    ? 'bg-brand-soft text-brand-deep shadow-[inset_4px_0_0_var(--color-brand)]'
                    : 'text-muted-ink hover:bg-brand-soft/70',
            )}
        >
            <item.icon className="size-5" />
            {item.title}
        </Link>
    );
}

function RailCard({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-2xl border bg-card p-4 shadow-[0_6px_25px_rgba(6,23,35,0.08)]">
            <h3 className="mb-2.5 text-[15px] font-bold">{title}</h3>
            {children}
        </section>
    );
}
