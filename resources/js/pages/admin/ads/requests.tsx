import { Head } from '@inertiajs/react';
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
import type { Paginated, SelectOption } from '@/types/ads';

type Call = {
    id: number;
    at: string | null;
    provider: string;
    operation: string;
    outcome: string;
    http_status: number | null;
    latency_ms: number | null;
    error: string | null;
    position: number;
    request_id: number | null;
    request_outcome: string | null;
    resolved_provider: string | null;
};

type Props = {
    calls: Paginated<Call>;
    stats: {
        provider: string;
        total: number;
        success: number;
        failed: number;
        avg_latency_ms: number | null;
    }[];
    filters: Partial<Record<'provider' | 'outcome' | 'operation', string>>;
    options: { providers: SelectOption[]; outcomes: SelectOption[] };
};

export default function AdsRequests({ calls, stats, filters, options }: Props) {
    const form = useFilters('/admin/ads/requests', {
        provider: filters.provider ?? '',
        outcome: filters.outcome ?? '',
        operation: filters.operation ?? '',
    });

    return (
        <>
            <Head title="Provider requests" />
            <AdminPage
                title="Provider requests"
                description="Every call made to a provider: how often it succeeds, how fast it answers, and where the fallback chain moved on."
            >
                <section
                    aria-label="Last 7 days"
                    className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {stats.length === 0 ? (
                        <div className="sm:col-span-2 xl:col-span-4">
                            <AdminEmpty>
                                No provider calls in the last 7 days.
                            </AdminEmpty>
                        </div>
                    ) : (
                        stats.map((row) => (
                            <StatTile
                                key={row.provider}
                                label={`${row.provider} · 7 days`}
                                value={`${
                                    row.total === 0
                                        ? 0
                                        : Math.round(
                                              (row.success / row.total) * 100,
                                          )
                                }% success`}
                                hint={`${row.total} calls · ${row.failed} failed${
                                    row.avg_latency_ms !== null
                                        ? ` · ${row.avg_latency_ms} ms avg`
                                        : ''
                                }`}
                                tone={row.failed > 0 ? 'red' : 'blue'}
                            />
                        ))
                    )}
                </section>

                <AdminSectionCard
                    title="Calls"
                    description="The # column is the position in the fallback chain for that request."
                >
                    <FilterBar
                        onApply={form.apply}
                        onReset={form.reset}
                        active={form.active}
                    >
                        <SelectFilter
                            label="Provider"
                            value={form.values.provider}
                            onChange={(value) => form.set('provider', value)}
                            options={options.providers}
                        />
                        <SelectFilter
                            label="Outcome"
                            value={form.values.outcome}
                            onChange={(value) => form.set('outcome', value)}
                            options={options.outcomes}
                        />
                        <SelectFilter
                            label="Operation"
                            value={form.values.operation}
                            onChange={(value) => form.set('operation', value)}
                            options={[
                                {
                                    value: 'fetch_offers',
                                    label: 'Fetch surveys',
                                },
                                { value: 'reporting', label: 'Reporting' },
                                {
                                    value: 'test_connection',
                                    label: 'Connection test',
                                },
                            ]}
                        />
                    </FilterBar>

                    {calls.data.length === 0 ? (
                        <AdminEmpty>No calls match these filters.</AdminEmpty>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>When</TableHead>
                                        <TableHead>Request</TableHead>
                                        <TableHead>#</TableHead>
                                        <TableHead>Provider</TableHead>
                                        <TableHead>Operation</TableHead>
                                        <TableHead>Outcome</TableHead>
                                        <TableHead>HTTP</TableHead>
                                        <TableHead className="text-right">
                                            Latency
                                        </TableHead>
                                        <TableHead>Detail</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {calls.data.map((call) => (
                                        <TableRow key={call.id}>
                                            <TableCell className="whitespace-nowrap">
                                                {formatDateTime(call.at)}
                                            </TableCell>
                                            <TableCell>
                                                {call.request_id ? (
                                                    <span>
                                                        #{call.request_id}
                                                        {call.resolved_provider ? (
                                                            <span className="text-xs text-muted-foreground">
                                                                {' '}
                                                                →{' '}
                                                                {
                                                                    call.resolved_provider
                                                                }
                                                            </span>
                                                        ) : call.request_outcome ===
                                                          'no_fill' ? (
                                                            <span className="text-xs text-muted-foreground">
                                                                {' '}
                                                                no fill
                                                            </span>
                                                        ) : null}
                                                    </span>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {call.position}
                                            </TableCell>
                                            <TableCell>
                                                {call.provider}
                                            </TableCell>
                                            <TableCell>
                                                {call.operation.replaceAll(
                                                    '_',
                                                    ' ',
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    value={call.outcome}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                {call.http_status ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {call.latency_ms !== null
                                                    ? `${call.latency_ms} ms`
                                                    : '—'}
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                {call.error?.replaceAll(
                                                    '_',
                                                    ' ',
                                                ) ?? '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <Pagination page={calls} />
                        </>
                    )}
                </AdminSectionCard>
            </AdminPage>
        </>
    );
}

AdsRequests.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Advertising', href: '/admin/ads' },
        { title: 'Provider requests', href: '/admin/ads/requests' },
    ],
};
