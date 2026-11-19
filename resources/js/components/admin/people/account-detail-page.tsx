import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    MoreHorizontal,
    Pencil,
    RotateCcw,
    ShieldOff,
    Timer,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { AccountFormDialog } from '@/components/admin/people/account-form-dialog';
import { AdministratorDetail } from '@/components/admin/people/administrator-detail';
import { BanDialog } from '@/components/admin/people/ban-dialog';
import { SuspendDialog } from '@/components/admin/people/suspend-dialog';
import { UserEarningsPanel } from '@/components/admin/people/user-earnings-panel';
import type { PeopleDetailProps } from '@/components/admin/people/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

function statusClass(status: string): string {
    switch (status) {
        case 'active':
            return 'border-[#76d8f8]/40 bg-[#e7f7ff] text-[#0f6f8f]';
        case 'suspended':
            return 'border-amber-300 bg-amber-50 text-amber-800';
        case 'banned':
            return 'border-[#ed2948]/40 bg-[#ffedf0] text-[#ed2948]';
        default:
            return 'border-slate-300 bg-slate-100 text-slate-600';
    }
}

export function AccountDetailPage({
    account,
    capabilities,
    routes,
}: PeopleDetailProps) {
    const [editOpen, setEditOpen] = useState(false);
    const [banOpen, setBanOpen] = useState(false);
    const [suspendOpen, setSuspendOpen] = useState(false);
    const isAdministrator = account.role === 'administrator';

    return (
        <>
            <Head title={account.name} />

            {isAdministrator ? (
                <AdministratorDetail
                    account={account}
                    routes={routes}
                    onEdit={() => setEditOpen(true)}
                />
            ) : (
                <div className="flex h-full flex-1 flex-col gap-4 p-4">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="flex min-w-0 items-start gap-3">
                            <Button variant="outline" size="icon" asChild>
                                <Link href={routes.index}>
                                    <ArrowLeft className="size-4" />
                                </Link>
                            </Button>
                            <Avatar className="size-14 shrink-0">
                                <AvatarImage
                                    src={account.avatar ?? undefined}
                                    alt={account.name}
                                />
                                <AvatarFallback className="bg-[#0a1018] text-[#76d8f8]">
                                    {account.name.slice(0, 2).toUpperCase()}
                                </AvatarFallback>
                            </Avatar>
                            <div className="min-w-0">
                                <h1 className="truncate text-2xl font-semibold tracking-tight">
                                    {account.name}
                                </h1>
                                <p className="truncate text-sm text-muted-foreground">
                                    {account.email}
                                </p>
                                <div className="mt-2 flex flex-wrap gap-1.5">
                                    <Badge
                                        variant="outline"
                                        className={cn(
                                            'capitalize',
                                            statusClass(account.status),
                                        )}
                                    >
                                        {account.status}
                                    </Badge>
                                    <Badge
                                        variant="secondary"
                                        className="capitalize"
                                    >
                                        {account.role}
                                    </Badge>
                                    <Badge variant="outline">
                                        {account.verified
                                            ? 'Verified'
                                            : 'Unverified'}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline">
                                    Actions
                                    <MoreHorizontal className="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-48">
                                {account.status !== 'deleted' && (
                                    <DropdownMenuItem
                                        onSelect={() => setEditOpen(true)}
                                    >
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

                                {capabilities.can_moderate &&
                                    account.status !== 'deleted' && (
                                        <>
                                            <DropdownMenuSeparator />
                                            {account.is_banned ? (
                                                <DropdownMenuItem
                                                    onSelect={() =>
                                                        routes.unban &&
                                                        router.post(
                                                            routes.unban,
                                                            {},
                                                            {
                                                                onSuccess: () =>
                                                                    toast.success(
                                                                        'Ban lifted.',
                                                                    ),
                                                            },
                                                        )
                                                    }
                                                >
                                                    <ShieldOff className="size-4" />
                                                    Lift ban
                                                </DropdownMenuItem>
                                            ) : (
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    onSelect={() =>
                                                        setBanOpen(true)
                                                    }
                                                >
                                                    <Ban className="size-4" />
                                                    Ban…
                                                </DropdownMenuItem>
                                            )}
                                            {account.is_suspended ? (
                                                <DropdownMenuItem
                                                    onSelect={() =>
                                                        routes.unsuspend &&
                                                        router.post(
                                                            routes.unsuspend,
                                                            {},
                                                            {
                                                                onSuccess: () =>
                                                                    toast.success(
                                                                        'Suspension cleared.',
                                                                    ),
                                                            },
                                                        )
                                                    }
                                                >
                                                    <ShieldOff className="size-4" />
                                                    Clear suspension
                                                </DropdownMenuItem>
                                            ) : (
                                                !account.is_banned && (
                                                    <DropdownMenuItem
                                                        onSelect={() =>
                                                            setSuspendOpen(true)
                                                        }
                                                    >
                                                        <Timer className="size-4" />
                                                        Suspend…
                                                    </DropdownMenuItem>
                                                )
                                            )}
                                        </>
                                    )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <section className="rounded-2xl border border-[#d4e7ed] bg-white p-4 shadow-sm">
                        <h2 className="mb-3 text-sm font-semibold text-[#0a1018]">
                            Profile
                        </h2>
                        <dl className="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                            <Row
                                label="Joined"
                                value={account.created_at ?? '—'}
                            />
                            <Row
                                label="Email status"
                                value={
                                    account.verified
                                        ? 'Verified'
                                        : 'Unverified'
                                }
                            />
                            {account.ban_reason && (
                                <Row
                                    label="Ban reason"
                                    value={account.ban_reason}
                                    danger
                                />
                            )}
                            {account.suspended_until && (
                                <Row
                                    label="Suspended until"
                                    value={new Date(
                                        account.suspended_until,
                                    ).toLocaleString()}
                                />
                            )}
                            {account.suspension_reason && (
                                <Row
                                    label="Suspension reason"
                                    value={account.suspension_reason}
                                />
                            )}
                        </dl>
                    </section>

                    {account.wallet && account.earnings && (
                        <UserEarningsPanel
                            showUrl={routes.show}
                            wallet={account.wallet}
                            earnings={account.earnings}
                        />
                    )}

                    <section className="rounded-2xl border border-dashed border-[#d4e7ed] bg-white p-4">
                        <h2 className="text-sm font-semibold">Activity</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Login, moderation, and account events will show
                            here.
                        </p>
                        <div className="mt-4 rounded-xl bg-[#f7fcff] px-4 py-8 text-center text-sm text-muted-foreground">
                            No activity yet
                        </div>
                    </section>
                </div>
            )}

            <AccountFormDialog
                open={editOpen}
                onOpenChange={setEditOpen}
                mode="edit"
                role={account.role}
                account={account}
                storeUrl={routes.index}
                updateUrl={routes.update}
            />

            {routes.ban && (
                <BanDialog
                    open={banOpen}
                    onOpenChange={setBanOpen}
                    name={account.name}
                    banUrl={routes.ban}
                />
            )}
            {routes.suspend && (
                <SuspendDialog
                    open={suspendOpen}
                    onOpenChange={setSuspendOpen}
                    name={account.name}
                    suspendUrl={routes.suspend}
                />
            )}
        </>
    );
}

function Row({
    label,
    value,
    danger = false,
}: {
    label: string;
    value: string;
    danger?: boolean;
}) {
    return (
        <div className="flex items-start justify-between gap-4 border-b border-[#eef5f8] py-2 last:border-0">
            <dt className="text-muted-foreground">{label}</dt>
            <dd
                className={cn(
                    'text-right font-medium',
                    danger && 'text-[#ed2948]',
                )}
            >
                {value}
            </dd>
        </div>
    );
}

