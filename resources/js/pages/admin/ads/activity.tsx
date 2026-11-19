import { Head, Link } from '@inertiajs/react';
import {
    AdminEmpty,
    AdminNotice,
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
import { formatDateTime, usd } from '@/lib/format';
import type { Paginated, PersonRef, SelectOption } from '@/types/ads';

type ActivityRow = {
    id: string;
    user: PersonRef | null;
    provider: string;
    survey: string | null;
    source: string;
    started_at: string | null;
    confirmed_at: string | null;
    status: string;
    publisher_revenue_usd: string;
    points: number;
    reward_status: string;
};

type Props = {
    activities: Paginated<ActivityRow>;
    filters: Partial<
        Record<
            | 'search'
            | 'user'
            | 'survey'
            | 'provider'
            | 'status'
            | 'reward_status'
            | 'from'
            | 'to',
            string
        >
    >;
    options: {
        providers: SelectOption[];
        statuses: SelectOption[];
        reward_statuses: SelectOption[];
    };
    timezone: string;
};

export default function AdsActivity({
    activities,
    filters,
    options,
    timezone,
}: Props) {
    const form = useFilters('/admin/ads/activity', {
        search: filters.search ?? '',
        user: filters.user ?? '',
        survey: filters.survey ?? '',
        provider: filters.provider ?? '',
        status: filters.status ?? '',
        reward_status: filters.reward_status ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });

    return (
        <>
            <Head title="Survey activity" />
            <AdminPage
                title="Survey activity"
                description="Every survey transaction across all users and providers. Open one to see its full timeline, the provider callback and the reward."
            >
                <AdminNotice>
                    “Confirmed” is the time our server received the provider’s
                    verified postback. Times are shown in the settlement
                    timezone ({timezone}).
                </AdminNotice>

                <AdminSectionCard title="Transactions">
                    <FilterBar
                        onApply={form.apply}
                        onReset={form.reset}
                        active={form.active}
                    >
                        <TextFilter
                            label="Search"
                            value={form.values.search}
                            onChange={(value) => form.set('search', value)}
                            placeholder="User, survey, transaction id…"
                        />
                        <TextFilter
                            label="User ID"
                            value={form.values.user}
                            onChange={(value) => form.set('user', value)}
                            placeholder="e.g. 42"
                        />
                        <TextFilter
                            label="Survey ID"
                            value={form.values.survey}
                            onChange={(value) => form.set('survey', value)}
                        />
                        <SelectFilter
                            label="Provider"
                            value={form.values.provider}
                            onChange={(value) => form.set('provider', value)}
                            options={options.providers}
                        />
                        <SelectFilter
                            label="Approval status"
                            value={form.values.status}
                            onChange={(value) => form.set('status', value)}
                            options={options.statuses}
                        />
                        <SelectFilter
                            label="Reward status"
                            value={form.values.reward_status}
                            onChange={(value) =>
                                form.set('reward_status', value)
                            }
                            options={options.reward_statuses}
                        />
                        <TextFilter
                            label="From"
                            type="date"
                            value={form.values.from}
                            onChange={(value) => form.set('from', value)}
                        />
                        <TextFilter
                            label="To"
                            type="date"
                            value={form.values.to}
                            onChange={(value) => form.set('to', value)}
                        />
                    </FilterBar>

                    {activities.data.length === 0 ? (
                        <AdminEmpty>
                            No survey transactions match these filters.
                        </AdminEmpty>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>User</TableHead>
                                        <TableHead>Survey</TableHead>
                                        <TableHead>Started at</TableHead>
                                        <TableHead>Confirmed at</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Publisher revenue
                                        </TableHead>
                                        <TableHead className="text-right">
                                            User reward
                                        </TableHead>
                                        <TableHead>Reward status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {activities.data.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell>
                                                {row.user ? (
                                                    <Link
                                                        href={`/admin/users/${row.user.id}`}
                                                        className="font-medium text-[#0f6f8f] hover:underline"
                                                    >
                                                        {row.user.name}
                                                    </Link>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        Unknown user
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Link
                                                    href={`/admin/ads/activity/${row.id}`}
                                                    className="font-medium text-[#0f6f8f] hover:underline"
                                                >
                                                    {row.survey ?? '—'}
                                                </Link>
                                                <div className="text-xs text-muted-foreground">
                                                    {row.provider}
                                                    {row.source ===
                                                    'postback_only'
                                                        ? ' · postback only'
                                                        : ''}
                                                </div>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {formatDateTime(
                                                    row.started_at,
                                                    timezone,
                                                )}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {formatDateTime(
                                                    row.confirmed_at,
                                                    timezone,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    value={row.status}
                                                />
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {usd(row.publisher_revenue_usd)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {row.points > 0
                                                    ? `${row.points} pts`
                                                    : '—'}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    value={row.reward_status}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <Pagination page={activities} />
                        </>
                    )}
                </AdminSectionCard>
            </AdminPage>
        </>
    );
}

AdsActivity.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Advertising', href: '/admin/ads' },
        { title: 'Survey activity', href: '/admin/ads/activity' },
    ],
};
