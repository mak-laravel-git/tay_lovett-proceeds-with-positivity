import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatTile,
    StatusBadge,
} from '@/components/admin/admin-page';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime, formatDay, usd } from '@/lib/format';
import type { Reconciliation } from './reconciliation';

type Props = {
    reconciliation: Reconciliation;
    country_breakdown: { country: string; usd: string }[];
    lines: {
        id: number;
        external_user_id: string;
        provider_usd: string;
        local_usd: string;
        difference_usd: string;
        flag: string;
    }[];
};

const flagHelp: Record<string, string> = {
    missing_local:
        'The provider reports revenue we have no postback for — a postback may be delayed or lost.',
    missing_provider:
        'We recorded revenue the provider did not report — it may be delayed, or the provider may have reversed it.',
    amount_mismatch:
        'Both sides have revenue for this user but the amounts differ.',
};

export default function AdsReconciliationShow({
    reconciliation,
    country_breakdown,
    lines,
}: Props) {
    return (
        <>
            <Head title={`Reconciliation ${reconciliation.date}`} />
            <AdminPage
                title={`Reconciliation · ${formatDay(reconciliation.date)}`}
                description={`${reconciliation.provider} · day boundaries in ${reconciliation.timezone}`}
                actions={
                    <Link
                        href="/admin/ads/reconciliation"
                        className="inline-flex items-center gap-1 text-sm font-medium text-[#0f6f8f] hover:underline"
                    >
                        <ArrowLeft className="size-4" /> All days
                    </Link>
                }
            >
                <div className="flex items-center gap-2">
                    <StatusBadge value={reconciliation.status} />
                    <span className="text-sm text-muted-foreground">
                        Last checked{' '}
                        {formatDateTime(
                            reconciliation.checked_at,
                            reconciliation.timezone,
                        )}
                    </span>
                </div>

                {reconciliation.last_error ? (
                    <AdminNotice tone="warn">
                        {reconciliation.last_error.replaceAll('_', ' ')}
                    </AdminNotice>
                ) : null}

                <section className="grid gap-3 sm:grid-cols-3">
                    <StatTile
                        label="Provider-reported"
                        tone="cyan"
                        value={usd(reconciliation.provider_revenue_usd)}
                        hint={
                            reconciliation.provider_revenue_usd === null
                                ? 'Not reported'
                                : undefined
                        }
                    />
                    <StatTile
                        label="Locally recorded"
                        value={usd(reconciliation.local_revenue_usd)}
                        hint={`${reconciliation.local_transaction_count} verified postback(s)`}
                    />
                    <StatTile
                        label="Difference"
                        tone={
                            reconciliation.status === 'discrepancy'
                                ? 'red'
                                : 'navy'
                        }
                        value={usd(reconciliation.difference_usd)}
                        hint="Provider − local"
                    />
                </section>

                <AdminSectionCard
                    title="By user"
                    description="Provider-reported versus recorded revenue per provider user ID. Differences are flags to investigate, not changes to any reward."
                >
                    {lines.length === 0 ? (
                        <AdminEmpty>No per-user data for this day.</AdminEmpty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Provider user ID</TableHead>
                                    <TableHead className="text-right">
                                        Provider
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Local
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Difference
                                    </TableHead>
                                    <TableHead>Flag</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {lines.map((line) => (
                                    <TableRow key={line.id}>
                                        <TableCell className="font-mono text-xs">
                                            {line.external_user_id}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {usd(line.provider_usd)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {usd(line.local_usd)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {usd(line.difference_usd)}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge value={line.flag} />
                                            {flagHelp[line.flag] ? (
                                                <div className="mt-0.5 max-w-xs text-xs text-muted-foreground">
                                                    {flagHelp[line.flag]}
                                                </div>
                                            ) : null}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </AdminSectionCard>

                <AdminSectionCard
                    title="Revenue by country"
                    description="From the provider’s report (postbacks carry no country)."
                >
                    {country_breakdown.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No country data.
                        </p>
                    ) : (
                        <ul className="grid gap-1 text-sm sm:max-w-sm">
                            {country_breakdown.map((row) => (
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
            </AdminPage>
        </>
    );
}

AdsReconciliationShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Advertising', href: '/admin/ads' },
        { title: 'Reconciliation', href: '/admin/ads/reconciliation' },
        { title: 'Day', href: '#' },
    ],
};
