import { Head } from '@inertiajs/react';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatTile,
    StatusBadge,
    formatCents,
} from '@/components/admin/admin-page';

type EntryRow = {
    id: number;
    entry_type: string;
    amount_cents: number;
    description: string | null;
    user: { id: number; name: string; email: string } | null;
    created_at: string | null;
};

export default function AdminRevenue({
    notice,
    wallet_totals,
    entries,
}: {
    notice: string;
    wallet_totals: {
        pending_cents: number;
        available_cents: number;
        reserved_cents: number;
        wallets: number;
    };
    entries: EntryRow[];
}) {
    return (
        <>
            <Head title="Revenue" />
            <AdminPage
                title="Revenue"
                description="Wallet balances and ledger activity across accounts."
            >
                <AdminNotice tone="warn">{notice}</AdminNotice>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <StatTile
                        label="Available"
                        value={formatCents(wallet_totals.available_cents)}
                        hint={`${wallet_totals.wallets} wallets`}
                        tone="blue"
                    />
                    <StatTile
                        label="Pending"
                        value={formatCents(wallet_totals.pending_cents)}
                        tone="navy"
                    />
                    <StatTile
                        label="Reserved"
                        value={formatCents(wallet_totals.reserved_cents)}
                        tone="cyan"
                    />
                    <StatTile
                        label="Ledger rows"
                        value={String(entries.length)}
                        hint="Latest 40 shown"
                        tone="red"
                    />
                </div>

                <AdminSectionCard
                    title="Ledger"
                    description="Posted wallet movements only — no estimated ad revenue"
                >
                    {entries.length === 0 ? (
                        <AdminEmpty>
                            No ledger entries yet. Balances stay at zero until
                            verified credits or withdrawals are posted.
                        </AdminEmpty>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[720px] text-left text-sm">
                                <thead className="border-b text-muted-foreground">
                                    <tr>
                                        <th className="px-2 py-2.5 font-medium">
                                            User
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Type
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Amount
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Description
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            When
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {entries.map((entry) => (
                                        <tr
                                            key={entry.id}
                                            className="border-b border-[#eef5f8] last:border-0"
                                        >
                                            <td className="px-2 py-3">
                                                <p className="font-medium text-[#0a1018]">
                                                    {entry.user?.name ??
                                                        'Unknown'}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {entry.user?.email}
                                                </p>
                                            </td>
                                            <td className="px-2 py-3">
                                                <StatusBadge
                                                    value={entry.entry_type}
                                                />
                                            </td>
                                            <td className="px-2 py-3 font-medium tabular-nums text-[#0a1018]">
                                                {formatCents(entry.amount_cents)}
                                            </td>
                                            <td className="px-2 py-3 text-muted-foreground">
                                                {entry.description ?? '—'}
                                            </td>
                                            <td className="px-2 py-3 text-muted-foreground">
                                                {entry.created_at ?? '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </AdminSectionCard>
            </AdminPage>
        </>
    );
}

AdminRevenue.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Revenue', href: '/admin/revenue' },
    ],
};
