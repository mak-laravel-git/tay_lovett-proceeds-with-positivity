import { Head, Link } from '@inertiajs/react';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatusBadge,
    formatCents,
} from '@/components/admin/admin-page';

type LogUser = {
    id: number;
    name: string;
    role: string;
};

type LogRow = {
    id: number;
    driver: string;
    model: string;
    credential_label: string;
    input_tokens: number | null;
    output_tokens: number | null;
    reserved_cents: number;
    spent_cents: number;
    error_category: string | null;
    created_at: string | null;
    user: LogUser | null;
};

function userDetailHref(user: LogUser): string {
    return user.role === 'administrator'
        ? `/admin/administrators/${user.id}`
        : `/admin/users/${user.id}`;
}

export default function Usage({ logs }: { logs: LogRow[] }) {
    const spentTotal = logs.reduce((sum, log) => sum + log.spent_cents, 0);
    const reservedTotal = logs.reduce(
        (sum, log) => sum + log.reserved_cents,
        0,
    );

    return (
        <>
            <Head title="AI usage" />
            <AdminPage
                title="AI usage"
                description="Recent generation attempts with reserved estimates and reported spend."
            >
                <AdminNotice>
                    Costs shown here are reserved estimates. Missing token
                    counts are not treated as zero.
                </AdminNotice>

                <AdminSectionCard
                    title="Recent logs"
                    description={`Latest ${logs.length} · spent ${formatCents(spentTotal)} · reserved ${formatCents(reservedTotal)}`}
                >
                    {logs.length === 0 ? (
                        <AdminEmpty>
                            No generation logs yet. Usage appears after studio
                            or routing attempts.
                        </AdminEmpty>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[900px] text-left text-sm">
                                <thead className="border-b text-muted-foreground">
                                    <tr>
                                        <th className="px-2 py-2.5 font-medium">
                                            User
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Credential
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Model
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Tokens
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Reserved
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Spent
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Result
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            When
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {logs.map((log) => (
                                        <tr
                                            key={log.id}
                                            className="border-b border-[#eef5f8] last:border-0"
                                        >
                                            <td className="px-2 py-3">
                                                {log.user ? (
                                                    <Link
                                                        href={userDetailHref(
                                                            log.user,
                                                        )}
                                                        className="font-medium text-[#0a1018] underline-offset-2 hover:text-[#ed2948] hover:underline"
                                                    >
                                                        {log.user.name}
                                                    </Link>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-2 py-3">
                                                <p className="font-medium text-[#0a1018]">
                                                    {log.credential_label}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {log.driver}
                                                </p>
                                            </td>
                                            <td className="px-2 py-3">
                                                {log.model}
                                            </td>
                                            <td className="px-2 py-3 tabular-nums text-muted-foreground">
                                                {log.input_tokens ?? '—'} /{' '}
                                                {log.output_tokens ?? '—'}
                                            </td>
                                            <td className="px-2 py-3 tabular-nums">
                                                {formatCents(log.reserved_cents)}
                                            </td>
                                            <td className="px-2 py-3 tabular-nums font-medium">
                                                {formatCents(log.spent_cents)}
                                            </td>
                                            <td className="px-2 py-3">
                                                {log.error_category ? (
                                                    <StatusBadge
                                                        value="unhealthy"
                                                        label={
                                                            log.error_category
                                                        }
                                                    />
                                                ) : (
                                                    <StatusBadge
                                                        value="healthy"
                                                        label="ok"
                                                    />
                                                )}
                                            </td>
                                            <td className="px-2 py-3 text-muted-foreground">
                                                {log.created_at ?? '—'}
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

Usage.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Usage', href: '/admin/ai/usage' },
    ],
};
