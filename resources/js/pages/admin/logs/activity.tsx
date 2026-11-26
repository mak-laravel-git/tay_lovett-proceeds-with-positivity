import { Head, Link } from '@inertiajs/react';
import {
    AdminEmpty,
    AdminPage,
    AdminSectionCard,
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

type Event = {
    id: number;
    at: string | null;
    user: PersonRef | null;
    run_id: string | null;
    category: string;
    event: string;
    level: string;
    context: Record<string, unknown> | null;
    ip: string | null;
};

type Props = {
    events: Paginated<Event>;
    filters: Record<string, string>;
    options: { categories: SelectOption[]; levels: SelectOption[] };
};

export default function ActivityLog({ events, filters, options }: Props) {
    const filter = useFilters('/admin/logs/activity', {
        category: filters.category ?? '',
        level: filters.level ?? '',
        user: filters.user ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });

    return (
        <>
            <Head title="Activity log" />
            <AdminPage
                title="Activity log"
                description="What users did in the quote flow, errors, and changes administrators made to the configuration."
            >
                <AdminSectionCard
                    title="Events"
                    description={`${events.total} in total`}
                >
                    <FilterBar
                        onApply={filter.apply}
                        onReset={filter.reset}
                        active={filter.active}
                    >
                        <SelectFilter
                            label="Area"
                            value={filter.values.category}
                            options={options.categories}
                            onChange={(value) => filter.set('category', value)}
                        />
                        <SelectFilter
                            label="Level"
                            value={filter.values.level}
                            options={options.levels}
                            onChange={(value) => filter.set('level', value)}
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

                    {events.data.length === 0 ? (
                        <AdminEmpty>No events match.</AdminEmpty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Area</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Detail</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {events.data.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell>
                                            {formatDateTime(row.at)}
                                        </TableCell>
                                        <TableCell>
                                            {row.user?.name ?? '—'}
                                            {row.ip ? (
                                                <div className="text-xs text-muted-foreground">
                                                    {row.ip}
                                                </div>
                                            ) : null}
                                        </TableCell>
                                        <TableCell>{row.category}</TableCell>
                                        <TableCell>
                                            {row.run_id ? (
                                                <Link
                                                    href={`/admin/workflow/runs/${row.run_id}`}
                                                    className="underline"
                                                >
                                                    {row.event.replaceAll(
                                                        '_',
                                                        ' ',
                                                    )}
                                                </Link>
                                            ) : (
                                                row.event.replaceAll('_', ' ')
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge value={row.level} />
                                        </TableCell>
                                        <TableCell className="max-w-md font-mono text-xs break-all whitespace-normal">
                                            {row.context
                                                ? JSON.stringify(row.context)
                                                : '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                    <Pagination page={events} />
                </AdminSectionCard>
            </AdminPage>
        </>
    );
}

ActivityLog.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Logs', href: '/admin/logs/activity' },
        { title: 'Activity', href: '/admin/logs/activity' },
    ],
};
