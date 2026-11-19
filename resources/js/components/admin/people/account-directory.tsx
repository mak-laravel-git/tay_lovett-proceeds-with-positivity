import { Link, router } from '@inertiajs/react';
import {
    Ban,
    Eye,
    MoreHorizontal,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    ShieldOff,
    Timer,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { AccountFormDialog } from '@/components/admin/people/account-form-dialog';
import { BanDialog } from '@/components/admin/people/ban-dialog';
import { SuspendDialog } from '@/components/admin/people/suspend-dialog';
import type {
    AccountStatus,
    PeopleAccount,
    PeopleCapabilities,
    PeoplePageProps,
} from '@/components/admin/people/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

type AccountRoutes = {
    show: string;
    update: string;
    destroy: string;
    restore: string;
    ban?: string;
    unban?: string;
    suspend?: string;
    unsuspend?: string;
};

const statusTabs: { value: AccountStatus; label: string }[] = [
    { value: 'active', label: 'Active' },
    { value: 'suspended', label: 'Suspended' },
    { value: 'banned', label: 'Banned' },
    { value: 'deleted', label: 'Trash' },
];

type AccountDirectoryProps = PeoplePageProps & {
    indexUrl: string;
    storeUrl: string;
    routesFor: (account: PeopleAccount) => AccountRoutes;
};

export function AccountDirectory({
    title,
    role,
    status,
    search,
    accounts,
    capabilities,
    indexUrl,
    storeUrl,
    routesFor,
}: AccountDirectoryProps) {
    const [query, setQuery] = useState(search);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState<'create' | 'edit'>('create');
    const [editing, setEditing] = useState<PeopleAccount | null>(null);
    const [banTarget, setBanTarget] = useState<PeopleAccount | null>(null);
    const [suspendTarget, setSuspendTarget] = useState<PeopleAccount | null>(
        null,
    );

    const noun = role === 'administrator' ? 'administrator' : 'user';

    const visibleTabs = useMemo(
        () =>
            statusTabs.filter((tab) => {
                if (role === 'administrator') {
                    return tab.value === 'active' || tab.value === 'deleted';
                }

                return true;
            }),
        [role],
    );

    function visit(next: { status?: AccountStatus; search?: string }) {
        router.get(
            indexUrl,
            {
                status: next.status ?? status,
                search: next.search ?? query,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function openCreate() {
        setFormMode('create');
        setEditing(null);
        setFormOpen(true);
    }

    function openEdit(account: PeopleAccount) {
        setFormMode('edit');
        setEditing(account);
        setFormOpen(true);
    }

    return (
        <div className="flex h-full flex-1 flex-col gap-4 p-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Manage {noun} accounts from the People sidebar.
                    </p>
                </div>
                <Button
                    type="button"
                    className="bg-[#ed2948] text-white hover:bg-[#d61f3d]"
                    onClick={openCreate}
                >
                    <Plus className="size-4" />
                    Create {noun}
                </Button>
            </div>

            <Card className="gap-0 overflow-hidden border-[#76d8f8]/35 bg-white py-0 shadow-sm">
                <div className="h-1.5 bg-gradient-to-r from-[#76d8f8] to-[#ed2948]" />
                <CardHeader className="gap-3 pt-4 pb-3">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <CardTitle>Directory</CardTitle>
                            <CardDescription>
                                {accounts.length}{' '}
                                {accounts.length === 1 ? 'account' : 'accounts'}
                            </CardDescription>
                        </div>
                        <form
                            className="relative w-full max-w-sm"
                            onSubmit={(event) => {
                                event.preventDefault();
                                visit({ search: query });
                            }}
                        >
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={query}
                                onChange={(event) =>
                                    setQuery(event.target.value)
                                }
                                placeholder={`Search ${noun}s...`}
                                className="pl-9"
                            />
                        </form>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {visibleTabs.map((tab) => (
                            <button
                                key={tab.value}
                                type="button"
                                onClick={() => visit({ status: tab.value })}
                                className={cn(
                                    'rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors',
                                    status === tab.value
                                        ? 'border-[#ed2948] bg-[#ffedf0] text-[#ed2948]'
                                        : 'border-[#d4e7ed] bg-white text-[#526475] hover:border-[#76d8f8]',
                                )}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                </CardHeader>
                <CardContent className="overflow-x-auto pb-4">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="border-b text-muted-foreground">
                            <tr>
                                <th className="px-2 py-2.5 font-medium">
                                    Account
                                </th>
                                <th className="px-2 py-2.5 font-medium">
                                    Status
                                </th>
                                <th className="px-2 py-2.5 font-medium">
                                    Verified
                                </th>
                                <th className="px-2 py-2.5 font-medium">
                                    Joined
                                </th>
                                <th className="px-2 py-2.5 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {accounts.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-2 py-8 text-center text-muted-foreground"
                                    >
                                        No {noun}s in this view.
                                    </td>
                                </tr>
                            )}
                            {accounts.map((account) => (
                                <tr
                                    key={account.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-2 py-2.5">
                                        <Link
                                            href={routesFor(account).show}
                                            className="flex items-center gap-3"
                                        >
                                            <Avatar className="size-9">
                                                <AvatarImage
                                                    src={
                                                        account.avatar ??
                                                        undefined
                                                    }
                                                    alt={account.name}
                                                />
                                                <AvatarFallback className="bg-[#0a1018] text-[10px] text-[#76d8f8]">
                                                    {account.name
                                                        .slice(0, 2)
                                                        .toUpperCase()}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span>
                                                <span className="block font-medium text-[#0a1018]">
                                                    {account.name}
                                                </span>
                                                <span className="block text-xs text-muted-foreground">
                                                    {account.email}
                                                </span>
                                            </span>
                                        </Link>
                                    </td>
                                    <td className="px-2 py-2.5">
                                        <StatusPill status={account.status} />
                                    </td>
                                    <td className="px-2 py-2.5">
                                        {account.verified ? 'Yes' : 'No'}
                                    </td>
                                    <td className="px-2 py-2.5">
                                        {account.created_at ?? '—'}
                                    </td>
                                    <td className="px-2 py-2.5">
                                        <AccountRowActions
                                            account={account}
                                            capabilities={capabilities}
                                            routes={routesFor(account)}
                                            onEdit={() => openEdit(account)}
                                            onBan={() => setBanTarget(account)}
                                            onSuspend={() =>
                                                setSuspendTarget(account)
                                            }
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <AccountFormDialog
                open={formOpen}
                onOpenChange={setFormOpen}
                mode={formMode}
                role={role}
                account={editing}
                storeUrl={storeUrl}
                updateUrl={
                    editing ? routesFor(editing).update : undefined
                }
            />

            {banTarget?.id && routesFor(banTarget).ban && (
                <BanDialog
                    open={banTarget !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setBanTarget(null);
                        }
                    }}
                    name={banTarget.name}
                    banUrl={routesFor(banTarget).ban!}
                />
            )}

            {suspendTarget?.id && routesFor(suspendTarget).suspend && (
                <SuspendDialog
                    open={suspendTarget !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setSuspendTarget(null);
                        }
                    }}
                    name={suspendTarget.name}
                    suspendUrl={routesFor(suspendTarget).suspend!}
                />
            )}
        </div>
    );
}

function StatusPill({ status }: { status: AccountStatus }) {
    const styles: Record<AccountStatus, string> = {
        active: 'border-[#76d8f8]/40 bg-[#e7f7ff] text-[#0f6f8f]',
        suspended: 'border-amber-300 bg-amber-50 text-amber-800',
        banned: 'border-[#ed2948]/40 bg-[#ffedf0] text-[#ed2948]',
        deleted: 'border-slate-300 bg-slate-100 text-slate-600',
    };

    return (
        <Badge variant="outline" className={cn('capitalize', styles[status])}>
            {status}
        </Badge>
    );
}

function AccountRowActions({
    account,
    capabilities,
    routes,
    onEdit,
    onBan,
    onSuspend,
}: {
    account: PeopleAccount;
    capabilities: PeopleCapabilities;
    routes: AccountRoutes;
    onEdit: () => void;
    onBan: () => void;
    onSuspend: () => void;
}) {
    return (
        <div className="flex justify-end">
            <DropdownMenu>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <DropdownMenuTrigger asChild>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="size-8 text-[#41516b] hover:bg-[#e7f7ff] hover:text-[#0a1018]"
                                aria-label="Actions"
                            >
                                <MoreHorizontal className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                    </TooltipTrigger>
                    <TooltipContent side="top">Actions</TooltipContent>
                </Tooltip>

                <DropdownMenuContent align="end" className="w-48">
                    <DropdownMenuItem asChild>
                        <Link href={routes.show}>
                            <Eye className="size-4" />
                            View
                        </Link>
                    </DropdownMenuItem>

                    {account.status !== 'deleted' && (
                        <DropdownMenuItem onSelect={onEdit}>
                            <Pencil className="size-4" />
                            Edit
                        </DropdownMenuItem>
                    )}

                    <DropdownMenuSeparator />

                    {account.status === 'deleted' ? (
                        <DropdownMenuItem
                            onSelect={() =>
                                router.post(
                                    routes.restore,
                                    {},
                                    {
                                        preserveScroll: true,
                                        onSuccess: () =>
                                            toast.success('Account restored.'),
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
                                        preserveScroll: true,
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
                                                    preserveScroll: true,
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
                                        onSelect={onBan}
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
                                                    preserveScroll: true,
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
                                        <DropdownMenuItem onSelect={onSuspend}>
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
    );
}
