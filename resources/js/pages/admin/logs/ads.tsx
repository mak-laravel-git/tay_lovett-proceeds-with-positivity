import { Head, Link } from '@inertiajs/react';
import {
    AdminEmpty,
    AdminPage,
    AdminSectionCard,
    StatTile,
    StatusBadge,
} from '@/components/admin/admin-page';
import {
    FilterBar,
    SelectFilter,
    TextFilter,
    useFilters,
} from '@/components/admin/ads/filters';
import { Pagination } from '@/components/admin/ads/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import type { Paginated, PersonRef, SelectOption } from '@/types/ads';

type Attempt = {
    id: string;
    run_id: string;
    user: PersonRef | null;
    step: string;
    status: string;
    ad_unit_path: string | null;
    error_detail: string | null;
    created_at: string | null;
    shown_at: string | null;
    granted_at: string | null;
    closed_at: string | null;
};

type Props = {
    attempts: Paginated<Attempt>;
    summary: { status: string; total: number }[];
    filters: Record<string, string>;
    options: { statuses: SelectOption[]; steps: SelectOption[] };
};

export default function AdLog({ attempts, summary, filters, options }: Props) {
    const filter = useFilters('/admin/logs/ads', {
        status: filters.status ?? '',
        step: filters.step ?? '',
        user: filters.user ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });

    return (
        <>
            <Head title="Rewarded ad activity" />
            <AdminPage
                title="Rewarded ad activity"
                description="Every rewarded ad requested by the quote workflow, as reported by the visitor's browser."
            >
                {summary.length > 0 ? (
                    <section
                        aria-label="Last 7 days"
                        className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        {summary.map((row) => (
                            <StatTile
                                key={row.status}
                                label={`${row.status} · 7 days`}
                                value={row.total.toLocaleString()}
                            />
                        ))}
                    </section>
                ) : null}

                <AdminSectionCard
                    title="Attempts"
                    description={`${attempts.total} in total`}
                >
                    <FilterBar
                        onApply={filter.apply}
                        onReset={filter.reset}
                        active={filter.active}
                    >
                        <SelectFilter
                            label="Status"
                            value={filter.values.status}
                            options={options.statuses}
                            onChange={(value) => filter.set('status', value)}
                        />
                        <SelectFilter
                            label="Step"
                            value={filter.values.step}
                            options={options.steps}
                            onChange={(value) => filter.set('step', value)}
                        />
                        <TextFilter
                            label="User name or email"
                            value={filter.values.user}
                            onChange={(value) => filter.set('user', value)}
                        />
                        <TextFilter
                            label="From"
                            type="date"
                            value={filter.values.from}
                            onChange={(value) => filter.set('from', value)}
                        />
                        <TextFilter
                            label="To"
                            type="date"
                            value={filter.values.to}
                            onChange={(value) => filter.set('to', value)}
                        />
                    </FilterBar>

                    {attempts.data.length === 0 ? (
                        <AdminEmpty>No ad attempts match.</AdminEmpty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Requested</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Step</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Shown</TableHead>
                                    <TableHead>Rewarded</TableHead>
                                    <TableHead>Closed</TableHead>
                                    <TableHead>Detail</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {attempts.data.map((attempt) => (
                                    <TableRow key={attempt.id}>
                                        <TableCell>
                                            <Link
                                                href={`/admin/workflow/runs/${attempt.run_id}`}
                                                className="underline"
                                            >
                                                {formatDateTime(
                                                    attempt.created_at,
                                                )}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            {attempt.user?.name ?? '—'}
                                        </TableCell>
                                        <TableCell>{attempt.step}</TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                value={attempt.status}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            {formatDateTime(attempt.shown_at)}
                                        </TableCell>
                                        <TableCell>
                                            {formatDateTime(attempt.granted_at)}
                                        </TableCell>
                                        <TableCell>
                                            {formatDateTime(attempt.closed_at)}
                                        </TableCell>
                                        <TableCell>
                                            {attempt.error_detail?.replaceAll(
                                                '_',
                                                ' ',
                                            ) ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                    <Pagination page={attempts} />
                </AdminSectionCard>
            </AdminPage>
        </>
    );
}

AdLog.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Logs', href: '/admin/logs/ads' },
        { title: 'Rewarded ads', href: '/admin/logs/ads' },
    ],
};
