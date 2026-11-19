import { Link } from '@inertiajs/react';
import { ShieldCheck, UserRound } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';

const tabs = [
    { title: 'Profile', href: '/settings/profile', icon: UserRound },
    { title: 'Security', href: '/settings/security', icon: ShieldCheck },
];

/**
 * Title and tab links shared by the account pages. The page itself is wrapped by AccountShell.
 */
export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="mx-auto w-full max-w-3xl px-4 py-6 min-[761px]:px-0">
            <header className="mb-5">
                <h1 className="text-2xl font-black tracking-tight text-ink">
                    Account
                </h1>
                <p className="mt-1 text-sm text-muted-ink">
                    Manage your profile and keep your account safe.
                </p>
            </header>

            <nav
                aria-label="Account sections"
                className="mb-5 inline-flex gap-1 rounded-xl bg-sky-pale p-1"
            >
                {tabs.map(({ title, href, icon: Icon }) => {
                    const active = isCurrentOrParentUrl(href);

                    return (
                        <Link
                            key={href}
                            href={href}
                            prefetch
                            aria-current={active ? 'page' : undefined}
                            className={cn(
                                'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-extrabold transition-colors',
                                active
                                    ? 'bg-brand text-white shadow-sm'
                                    : 'text-ink hover:bg-white/70',
                            )}
                        >
                            <Icon className="size-4" aria-hidden />
                            {title}
                        </Link>
                    );
                })}
            </nav>

            <div className="grid gap-5">{children}</div>
        </div>
    );
}
