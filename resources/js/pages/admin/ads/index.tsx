import { Head, Link } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatTile,
    StatusBadge,
} from '@/components/admin/admin-page';
import {
    type ChartConfig,
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import { formatDateTime, usd } from '@/lib/format';

type Props = {
    timezone: string;
    counts: {
        completed: number;
        approved: number;
        rejected: number;
        pending: number;
        unmatched: number;
    };
    points_distributed: number;
    point_value_set: boolean;
    estimated_remaining_usd: string | null;
    local_revenue: {
        today_usd: string;
        yesterday_usd: string;
        month_usd: string;
        total_usd: string;
    };
    provider_revenue: {
        yesterday_usd: string | null;
        month_usd: string | null;
        yesterday_status: string | null;
    };
    settled_revenue: {
        recorded: boolean;
        month_usd: string;
        total_usd: string;
    };
    last_reconciled_at: string | null;
    daily_chart: {
        date: string;
        local_usd: number;
        provider_usd: number | null;
    }[];
    countries: { country: string; usd: string }[];
    providers: {
        key: string;
        label: string;
        is_enabled: boolean;
        health: string;
        last_success_at: string | null;
        last_error_category: string | null;
    }[];
};

const chartConfig = {
    local_usd: { label: 'Recorded from postbacks', color: '#76d8f8' },
    provider_usd: { label: 'Provider-reported', color: '#ed2948' },
} satisfies ChartConfig;

export default function AdsOverview(props: Props) {
    const { counts, local_revenue, provider_revenue, settled_revenue } = props;

    return (
        <>
            <Head title="Ads & surveys" />
            <AdminPage
                title="Ads & surveys"
                description="Survey activity, revenue and rewards across all providers."
                actions={
                    <>
                        <Link
                            href="/admin/ads/activity"
                            className="text-sm font-medium text-[#0f6f8f] hover:underline"
                        >
                            Survey activity
                        </Link>
                        <Link
                            href="/admin/ads/providers"
                            className="text-sm font-medium text-[#0f6f8f] hover:underline"
                        >
                            Providers
                        </Link>
                    </>
                }
            >
                <AdminNotice tone="warn">
                    None of these revenue figures is cash received. Recorded
                    revenue is what verified provider postbacks told us;
                    provider-reported revenue is the provider’s own daily
                    report; only settled revenue entered by an administrator
                    reflects money actually paid.
                </AdminNotice>

                <section
                    aria-label="Revenue"
                    className="grid gap-4 lg:grid-cols-3"
                >
                    <AdminSectionCard
                        title="1 · Recorded from postbacks"
                        description="Reported by the provider to us when a survey completes."
                    >
                        <div className="grid grid-cols-3 gap-3">
                            <StatTile
                                label="Today"
                                value={usd(local_revenue.today_usd)}
                            />
                            <StatTile
                                label="Yesterday"
                                value={usd(local_revenue.yesterday_usd)}
                            />
                            <StatTile
                                label="This month"
                                value={usd(local_revenue.month_usd)}
                            />
                        </div>
                        <p className="mt-2 text-xs text-muted-foreground">
                            All time {usd(local_revenue.total_usd)} · days in{' '}
                            {props.timezone}
                        </p>
                    </AdminSectionCard>

                    <AdminSectionCard
                        title="2 · Provider-reported"
                        description="From the provider’s daily report, via reconciliation."
                    >
                        <div className="grid grid-cols-2 gap-3">
                            <StatTile
                                label="Yesterday"
                                tone="cyan"
                                value={usd(provider_revenue.yesterday_usd)}
                                hint={
                                    provider_revenue.yesterday_usd === null
                                        ? 'Not reported yet'
                                        : (provider_revenue.yesterday_status ??
                                          undefined)
                                }
                            />
                            <StatTile
                                label="This month"
                                tone="cyan"
                                value={usd(provider_revenue.month_usd)}
                                hint={
                                    provider_revenue.month_usd === null
                                        ? 'Not reported yet'
                                        : undefined
                                }
                            />
                        </div>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Last successful reconciliation:{' '}
                            {formatDateTime(
                                props.last_reconciled_at,
                                props.timezone,
                            )}
                            {' · '}
                            <Link
                                href="/admin/ads/reconciliation"
                                className="text-[#0f6f8f] hover:underline"
                            >
                                Open reconciliation
                            </Link>
                        </p>
                    </AdminSectionCard>

                    <AdminSectionCard
                        title="3 · Settled / paid"
                        description="Revenue the provider actually paid, recorded by an administrator."
                    >
                        {settled_revenue.recorded ? (
                            <div className="grid grid-cols-2 gap-3">
                                <StatTile
                                    label="This month"
                                    tone="navy"
                                    value={usd(settled_revenue.month_usd)}
                                />
                                <StatTile
                                    label="All time"
                                    tone="navy"
                                    value={usd(settled_revenue.total_usd)}
                                />
                            </div>
                        ) : (
                            <AdminEmpty>
                                No payouts recorded yet. Record one on the
                                reconciliation page when the provider pays.
                            </AdminEmpty>
                        )}
                    </AdminSectionCard>
                </section>

                <section
                    aria-label="Surveys"
                    className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6"
                >
                    <StatTile
                        label="Completed"
                        value={String(counts.completed)}
                        hint="Returned or confirmed"
                    />
                    <StatTile
                        label="Approved"
                        value={String(counts.approved)}
                        hint="Provider confirmed"
                    />
                    <StatTile
                        label="Rejected"
                        value={String(counts.rejected)}
                        hint="Disqualified / over quota"
                        tone="red"
                    />
                    <StatTile
                        label="Pending confirmation"
                        value={String(counts.pending)}
                        hint="Waiting for the provider"
                    />
                    <StatTile
                        label="Points distributed"
                        value={props.points_distributed.toLocaleString()}
                        tone="cyan"
                    />
                    <StatTile
                        label="Est. remaining revenue"
                        value={
                            props.estimated_remaining_usd === null
                                ? '—'
                                : usd(props.estimated_remaining_usd)
                        }
                        hint={
                            props.point_value_set
                                ? 'Estimate: recorded revenue − points × value'
                                : 'Set a point value in Rewards'
                        }
                        tone="navy"
                    />
                </section>

                {counts.unmatched > 0 ? (
                    <AdminNotice tone="warn">
                        {counts.unmatched} confirmed payout(s) could not be tied
                        to a survey started in this app, so they earned no
                        points.{' '}
                        <Link
                            href="/admin/ads/activity?status=approved"
                            className="font-medium underline"
                        >
                            Review activity
                        </Link>
                    </AdminNotice>
                ) : null}

                <div className="grid gap-4 xl:grid-cols-3">
                    <AdminSectionCard
                        title="Daily revenue (last 14 days)"
                        description="Recorded from postbacks versus provider-reported, in US dollars."
                        className="xl:col-span-2"
                    >
                        <ChartContainer
                            config={chartConfig}
                            className="aspect-auto h-[260px] w-full"
                        >
                            <BarChart
                                accessibilityLayer
                                data={props.daily_chart}
                                margin={{ left: 8, right: 8, top: 8 }}
                            >
                                <CartesianGrid vertical={false} />
                                <XAxis
                                    dataKey="date"
                                    tickLine={false}
                                    axisLine={false}
                                    tickMargin={8}
                                    tickFormatter={(value: string) =>
                                        value.slice(5)
                                    }
                                />
                                <YAxis
                                    tickLine={false}
                                    axisLine={false}
                                    width={44}
                                    tickFormatter={(value: number) =>
                                        `$${value}`
                                    }
                                />
                                <ChartTooltip
                                    content={
                                        <ChartTooltipContent indicator="dashed" />
                                    }
                                />
                                <Bar
                                    dataKey="local_usd"
                                    fill="var(--color-local_usd)"
                                    radius={4}
                                />
                                <Bar
                                    dataKey="provider_usd"
                                    fill="var(--color-provider_usd)"
                                    radius={4}
                                />
                            </BarChart>
                        </ChartContainer>
                    </AdminSectionCard>

                    <div className="grid gap-4">
                        <AdminSectionCard title="Providers">
                            <ul className="grid gap-3">
                                {props.providers.map((provider) => (
                                    <li
                                        key={provider.key}
                                        className="flex items-start justify-between gap-2 text-sm"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {provider.label}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {provider.is_enabled
                                                    ? 'Enabled'
                                                    : 'Disabled'}
                                                {provider.last_success_at
                                                    ? ` · last success ${formatDateTime(provider.last_success_at, props.timezone)}`
                                                    : ''}
                                                {provider.last_error_category
                                                    ? ` · ${provider.last_error_category.replaceAll('_', ' ')}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <StatusBadge value={provider.health} />
                                    </li>
                                ))}
                            </ul>
                        </AdminSectionCard>

                        <AdminSectionCard
                            title="Revenue by country"
                            description="Provider reports only (last 30 days); postbacks carry no country."
                        >
                            {props.countries.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No country data yet.
                                </p>
                            ) : (
                                <ul className="grid gap-1 text-sm">
                                    {props.countries.map((row) => (
                                        <li
                                            key={row.country}
                                            className="flex justify-between"
                                        >
                                            <span>{row.country}</span>
                                            <span className="tabular-nums">
                                                {usd(row.usd)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </AdminSectionCard>
                    </div>
                </div>
            </AdminPage>
        </>
    );
}

AdsOverview.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Advertising', href: '/admin/ads' },
    ],
};
