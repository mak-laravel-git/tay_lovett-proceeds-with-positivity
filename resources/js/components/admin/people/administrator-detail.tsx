import { Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Bot,
    Clapperboard,
    KeyRound,
    LayoutGrid,
    MailCheck,
    MoreHorizontal,
    Pencil,
    RotateCcw,
    Shield,
    ShieldCheck,
    Trash2,
    Users,
    WalletCards,
} from 'lucide-react';
import { toast } from 'sonner';
import type { PeopleAccount, PeopleDetailRoutes } from '@/components/admin/people/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

const accessAreas = [
    {
        title: 'Overview',
        description: 'Operational snapshot and charts',
        href: '/admin',
        icon: LayoutGrid,
        tone: 'from-[#0a1018] to-[#1a3344] text-white',
    },
    {
        title: 'People',
        description: 'Administrators and users',
        href: '/admin/users',
        icon: Users,
        tone: 'from-[#dff6ff] to-[#e7f7ff] text-[#0a1018]',
    },
    {
        title: 'Moderation',
        description: 'Quote review pipeline',
        href: '/admin/quotes',
        icon: ShieldCheck,
        tone: 'from-[#ffedf0] to-[#fff5f7] text-[#ed2948]',
    },
    {
        title: 'Withdrawals',
        description: 'Manual payout review',
        href: '/admin/withdrawals',
        icon: WalletCards,
        tone: 'from-[#eef9fd] to-white text-[#0f6f8f]',
    },
    {
        title: 'Advertisements',
        description: 'Campaign inventory',
        href: '/admin/advertisements',
        icon: Clapperboard,
        tone: 'from-[#fff0f3] to-[#e7f7ff] text-[#0a1018]',
    },
    {
        title: 'AI management',
        description: 'Providers, models, routing',
        href: '/admin/ai/providers',
        icon: Bot,
        tone: 'from-[#0a1018] via-[#142433] to-[#b21c39] text-white',
    },
] as const;

type AdministratorDetailProps = {
    account: PeopleAccount;
    routes: PeopleDetailRoutes;
    onEdit: () => void;
};

export function AdministratorDetail({
    account,
    routes,
    onEdit,
}: AdministratorDetailProps) {
    return (
        <div className="flex h-full flex-1 flex-col gap-4 p-4">
            <section className="relative overflow-hidden rounded-3xl border border-[#243846] bg-[#0a1018] text-white shadow-sm">
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_12%_20%,#16506b_0,transparent_34%),radial-gradient(circle_at_88%_10%,#ab1937_0,transparent_28%)]" />
                <div className="relative flex flex-wrap items-start justify-between gap-4 p-5 sm:p-6">
                    <div className="flex min-w-0 items-start gap-4">
                        <Button
                            variant="secondary"
                            size="icon"
                            className="shrink-0 border border-[#345163] bg-[#182c38] text-white hover:bg-[#243846]"
                            asChild
                        >
                            <Link href={routes.index}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <Avatar className="size-16 border-2 border-[#76d8f8]/40">
                            <AvatarImage
                                src={account.avatar ?? undefined}
                                alt={account.name}
                            />
                            <AvatarFallback className="bg-[#182c38] text-lg text-[#76d8f8]">
                                {account.name.slice(0, 2).toUpperCase()}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <p className="text-xs font-semibold tracking-[0.18em] text-[#76d8f8] uppercase">
                                    Administrator
                                </p>
                                <Badge className="border-0 bg-[#ed2948] text-white capitalize">
                                    {account.status}
                                </Badge>
                            </div>
                            <h1 className="mt-1 truncate text-2xl font-black tracking-tight sm:text-3xl">
                                {account.name}
                            </h1>
                            <p className="mt-1 truncate text-sm text-[#9ec3d1]">
                                {account.email}
                            </p>
                            <div className="mt-3 flex flex-wrap gap-2 text-xs">
                                <span className="rounded-full border border-[#437087] bg-[#16354a] px-3 py-1">
                                    Full platform access
                                </span>
                                <span className="rounded-full border border-[#ed2948]/50 bg-[#801c31] px-3 py-1">
                                    People · Ops · AI
                                </span>
                            </div>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button className="border border-[#345163] bg-[#182c38] text-white hover:bg-[#243846]">
                                Actions
                                <MoreHorizontal className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                            {account.status !== 'deleted' && (
                                <DropdownMenuItem onSelect={onEdit}>
                                    <Pencil className="size-4" />
                                    Edit
                                </DropdownMenuItem>
                            )}
                            {account.status === 'deleted' ? (
                                <DropdownMenuItem
                                    onSelect={() =>
                                        router.post(
                                            routes.restore,
                                            {},
                                            {
                                                onSuccess: () =>
                                                    toast.success(
                                                        'Account restored.',
                                                    ),
                                            },
                                        )
                                    }
                                >
                                    <RotateCcw className="size-4" />
                                    Restore
                                </DropdownMenuItem>
                            ) : (
                                <DropdownMenuItem
                                    variant="destructive"
                                    onSelect={() => {
                                        if (
                                            confirm(
                                                `Move ${account.name} to trash?`,
                                            )
                                        ) {
                                            router.delete(routes.destroy, {
                                                onSuccess: () =>
                                                    toast.success(
                                                        'Account moved to trash.',
                                                    ),
                                            });
                                        }
                                    }}
                                >
                                    <Trash2 className="size-4" />
                                    Soft delete
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </section>

            <div className="grid gap-3 sm:grid-cols-3">
                <SecurityCard
                    icon={MailCheck}
                    label="Email"
                    value={account.verified ? 'Verified' : 'Unverified'}
                    ok={account.verified}
                />
                <SecurityCard
                    icon={KeyRound}
                    label="Two-factor"
                    value={
                        account.two_factor_enabled ? 'Enabled' : 'Not enabled'
                    }
                    ok={Boolean(account.two_factor_enabled)}
                />
                <SecurityCard
                    icon={Shield}
                    label="Role gate"
                    value="Administrator"
                    ok
                />
            </div>

            <div className="grid gap-4 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.3fr)]">
                <section className="rounded-2xl border border-[#d4e7ed] bg-white p-4 shadow-sm">
                    <h2 className="text-sm font-semibold text-[#0a1018]">
                        Account profile
                    </h2>
                    <dl className="mt-3 grid gap-0 text-sm">
                        <MetaRow label="Joined" value={account.created_at ?? '—'} />
                        <MetaRow
                            label="Last updated"
                            value={
                                account.updated_at
                                    ? new Date(
                                          account.updated_at,
                                      ).toLocaleString()
                                    : '—'
                            }
                        />
                        <MetaRow label="Account ID" value={`#${account.id}`} />
                        <MetaRow
                            label="Login email"
                            value={account.email}
                        />
                        <MetaRow
                            label="Status"
                            value={account.status}
                            emphasize
                        />
                    </dl>
                </section>

                <section className="rounded-2xl border border-[#d4e7ed] bg-white p-4 shadow-sm">
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <h2 className="text-sm font-semibold text-[#0a1018]">
                            Access map
                        </h2>
                        <Badge
                            variant="outline"
                            className="border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]"
                        >
                            Quick jump
                        </Badge>
                    </div>
                    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {accessAreas.map((area) => (
                            <Link
                                key={area.href}
                                href={area.href}
                                className={cn(
                                    'rounded-2xl bg-gradient-to-br p-3 transition-transform hover:-translate-y-0.5',
                                    area.tone,
                                )}
                            >
                                <area.icon className="size-4 opacity-90" />
                                <p className="mt-2 text-sm font-semibold">
                                    {area.title}
                                </p>
                                <p className="mt-0.5 text-[11px] opacity-80">
                                    {area.description}
                                </p>
                            </Link>
                        ))}
                    </div>
                </section>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <section className="rounded-2xl border border-[#ed2948]/25 bg-gradient-to-br from-[#fff5f7] via-white to-white p-4 shadow-sm">
                    <h2 className="text-sm font-semibold text-[#0a1018]">
                        Responsibilities
                    </h2>
                    <ul className="mt-3 space-y-2 text-sm text-[#41516b]">
                        <li className="flex gap-2">
                            <span className="mt-1 size-1.5 shrink-0 rounded-full bg-[#ed2948]" />
                            Manage People directory and account lifecycle
                        </li>
                        <li className="flex gap-2">
                            <span className="mt-1 size-1.5 shrink-0 rounded-full bg-[#76d8f8]" />
                            Moderate quotes and review withdrawals
                        </li>
                        <li className="flex gap-2">
                            <span className="mt-1 size-1.5 shrink-0 rounded-full bg-[#0a1018]" />
                            Configure AI providers, routing, and usage
                        </li>
                    </ul>
                </section>

                <section className="rounded-2xl border border-[#76d8f8]/35 bg-gradient-to-br from-[#eef9fd] via-white to-white p-4 shadow-sm">
                    <h2 className="text-sm font-semibold text-[#0a1018]">
                        Admin activity
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Audit trail for edits, restores, and privileged actions
                        will land here.
                    </p>
                    <div className="mt-4 space-y-2">
                        {[
                            'Account created in People → Administrators',
                            'Can open every admin module from Access map',
                            'Ban/suspend tools stay user-only by design',
                        ].map((item) => (
                            <div
                                key={item}
                                className="rounded-xl border border-[#d4e7ed] bg-white px-3 py-2 text-sm text-[#41516b]"
                            >
                                {item}
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </div>
    );
}

function SecurityCard({
    icon: Icon,
    label,
    value,
    ok,
}: {
    icon: typeof Shield;
    label: string;
    value: string;
    ok: boolean;
}) {
    return (
        <div
            className={cn(
                'rounded-2xl border p-4 shadow-sm',
                ok
                    ? 'border-[#76d8f8]/45 bg-gradient-to-br from-[#e7f7ff] to-white'
                    : 'border-[#ed2948]/35 bg-gradient-to-br from-[#fff1f4] to-white',
            )}
        >
            <div className="flex items-center justify-between gap-2">
                <p className="text-xs font-semibold tracking-wide text-[#526475] uppercase">
                    {label}
                </p>
                <Icon
                    className={cn(
                        'size-4',
                        ok ? 'text-[#0f6f8f]' : 'text-[#ed2948]',
                    )}
                />
            </div>
            <p
                className={cn(
                    'mt-2 text-lg font-semibold',
                    ok ? 'text-[#0a1018]' : 'text-[#ed2948]',
                )}
            >
                {value}
            </p>
        </div>
    );
}

function MetaRow({
    label,
    value,
    emphasize = false,
}: {
    label: string;
    value: string;
    emphasize?: boolean;
}) {
    return (
        <div className="flex items-start justify-between gap-4 border-b border-[#eef5f8] py-2.5 last:border-0">
            <dt className="text-muted-foreground">{label}</dt>
            <dd
                className={cn(
                    'max-w-[60%] text-right font-medium capitalize',
                    emphasize && 'text-[#ed2948]',
                )}
            >
                {value}
            </dd>
        </div>
    );
}
