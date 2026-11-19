import { Head, Link, router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatusBadge,
} from '@/components/admin/admin-page';
import { FilterField } from '@/components/admin/ads/filters';
import { Pagination } from '@/components/admin/ads/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime, formatDay, usd } from '@/lib/format';
import type { Paginated, SelectOption } from '@/types/ads';

export type Reconciliation = {
    id: number;
    provider: string;
    date: string;
    timezone: string;
    status: string;
    provider_revenue_usd: string | null;
    local_revenue_usd: string;
    difference_usd: string | null;
    local_transaction_count: number;
    flag_counts: Record<string, number>;
    last_error: string | null;
    fetched_at: string | null;
    rechecked_count: number;
    checked_at: string;
};

type Props = {
    reconciliations: Paginated<Reconciliation>;
    payouts: {
        id: number;
        provider: string;
        period_start: string;
        period_end: string;
        amount_usd: string;
        reference: string | null;
        received_at: string;
        recorded_by: string | null;
    }[];
    providers: SelectOption[];
    timezone: string;
};

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function AdsReconciliation({
    reconciliations,
    payouts,
    providers,
    timezone,
}: Props) {
    return (
        <>
            <Head title="Revenue reconciliation" />
            <AdminPage
                title="Revenue reconciliation"
                description="Each day, the provider’s reported revenue is compared with the revenue recorded from verified postbacks."
            >
                <AdminNotice>
                    This comparison is informational. A difference never
                    rejects, reverses or changes any individual survey or user
                    reward — reports can be delayed or rounded, and only the
                    provider’s confirmation approves a survey. Days follow the
                    settlement timezone ({timezone}); if the provider cuts its
                    day at a different time, surveys near midnight can cause
                    small differences.
                </AdminNotice>

                <AdminSectionCard
                    title="Daily reconciliation"
                    actions={<RunForm providers={providers} />}
                >
                    {reconciliations.data.length === 0 ? (
                        <AdminEmpty>
                            No reconciliation has run yet. It runs automatically
                            after midnight, or use “Run now”.
                        </AdminEmpty>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Provider</TableHead>
                                        <TableHead className="text-right">
                                            Provider-reported
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Locally recorded
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Difference
                                        </TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Last checked</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {reconciliations.data.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell>
                                                <Link
                                                    href={`/admin/ads/reconciliation/${row.id}`}
                                                    className="font-medium text-[#0f6f8f] hover:underline"
                                                >
                                                    {formatDay(row.date)}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                {row.provider}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {usd(row.provider_revenue_usd)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {usd(row.local_revenue_usd)}
                                                <div className="text-xs text-muted-foreground">
                                                    {
                                                        row.local_transaction_count
                                                    }{' '}
                                                    postback(s)
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {usd(row.difference_usd)}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    value={row.status}
                                                />
                                                {row.last_error ? (
                                                    <div className="mt-0.5 text-xs text-muted-foreground">
                                                        {row.last_error.replaceAll(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </div>
                                                ) : null}
                                            </TableCell>
                                            <TableCell className="text-xs whitespace-nowrap text-muted-foreground">
                                                {formatDateTime(
                                                    row.checked_at,
                                                    timezone,
                                                )}
                                                {row.rechecked_count > 0
                                                    ? ` · re-checked ${row.rechecked_count}×`
                                                    : ''}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <Pagination page={reconciliations} />
                        </>
                    )}
                </AdminSectionCard>

                <AdminSectionCard
                    title="Settled / paid revenue"
                    description="No provider API reports what was actually paid, so record each payout when it arrives. This is the only figure that reflects cash."
                >
                    <PayoutForm providers={providers} />
                    {payouts.length === 0 ? (
                        <p className="mt-4 text-sm text-muted-foreground">
                            No payouts recorded.
                        </p>
                    ) : (
                        <div className="mt-4">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Received</TableHead>
                                        <TableHead>Provider</TableHead>
                                        <TableHead>Period</TableHead>
                                        <TableHead className="text-right">
                                            Amount
                                        </TableHead>
                                        <TableHead>Reference</TableHead>
                                        <TableHead>Recorded by</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payouts.map((payout) => (
                                        <TableRow key={payout.id}>
                                            <TableCell>
                                                {formatDay(payout.received_at)}
                                            </TableCell>
                                            <TableCell>
                                                {payout.provider}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {formatDay(payout.period_start)}{' '}
                                                – {formatDay(payout.period_end)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {usd(payout.amount_usd)}
                                            </TableCell>
                                            <TableCell>
                                                {payout.reference ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {payout.recorded_by ?? '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </AdminSectionCard>
            </AdminPage>
        </>
    );
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

function RunForm({ providers }: { providers: SelectOption[] }) {
    const [provider, setProvider] = useState(providers[0]?.value ?? '');
    const [date, setDate] = useState('');
    const [processing, setProcessing] = useState(false);

    return (
        <form
            className="flex flex-wrap items-end gap-2"
            onSubmit={(event) => {
                event.preventDefault();
                router.post(
                    '/admin/ads/reconciliation/run',
                    { provider, date },
                    {
                        preserveScroll: true,
                        onStart: () => setProcessing(true),
                        onFinish: () => setProcessing(false),
                        onError: (errors) =>
                            toast.error(Object.values(errors)[0]),
                    },
                );
            }}
        >
            <FilterField label="Provider">
                <select
                    className={selectClass}
                    value={provider}
                    onChange={(event) => setProvider(event.target.value)}
                >
                    {providers.map((item) => (
                        <option key={item.value} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </select>
            </FilterField>
            <FilterField label="Day">
                <Input
                    type="date"
                    required
                    max={today()}
                    value={date}
                    onChange={(event) => setDate(event.target.value)}
                />
            </FilterField>
            <Button
                type="submit"
                size="sm"
                disabled={processing || date === ''}
            >
                <RefreshCw /> Run now
            </Button>
        </form>
    );
}

function PayoutForm({ providers }: { providers: SelectOption[] }) {
    const empty = {
        provider_key: providers[0]?.value ?? '',
        period_start: '',
        period_end: '',
        amount_usd: '',
        reference: '',
        received_at: '',
    };
    const [values, setValues] = useState(empty);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const set = (key: keyof typeof empty, value: string) =>
        setValues((current) => ({ ...current, [key]: value }));

    return (
        <form
            className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
            onSubmit={(event) => {
                event.preventDefault();
                router.post('/admin/ads/reconciliation/payouts', values, {
                    preserveScroll: true,
                    onStart: () => setProcessing(true),
                    onFinish: () => setProcessing(false),
                    onSuccess: () => {
                        setValues(empty);
                        setErrors({});
                    },
                    onError: (bag) => setErrors(bag),
                });
            }}
        >
            <FilterField label="Provider">
                <select
                    className={selectClass}
                    value={values.provider_key}
                    onChange={(event) =>
                        set('provider_key', event.target.value)
                    }
                >
                    {providers.map((item) => (
                        <option key={item.value} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </select>
            </FilterField>
            <FilterField label="Period start">
                <Input
                    type="date"
                    required
                    value={values.period_start}
                    onChange={(event) =>
                        set('period_start', event.target.value)
                    }
                />
                {errors.period_start ? (
                    <span className="text-xs text-destructive">
                        {errors.period_start}
                    </span>
                ) : null}
            </FilterField>
            <FilterField label="Period end">
                <Input
                    type="date"
                    required
                    value={values.period_end}
                    onChange={(event) => set('period_end', event.target.value)}
                />
                {errors.period_end ? (
                    <span className="text-xs text-destructive">
                        {errors.period_end}
                    </span>
                ) : null}
            </FilterField>
            <FilterField label="Amount received (USD)">
                <Input
                    inputMode="decimal"
                    required
                    placeholder="125.50"
                    value={values.amount_usd}
                    onChange={(event) => set('amount_usd', event.target.value)}
                />
                {errors.amount_usd ? (
                    <span className="text-xs text-destructive">
                        {errors.amount_usd}
                    </span>
                ) : null}
            </FilterField>
            <FilterField label="Date received">
                <Input
                    type="date"
                    required
                    value={values.received_at}
                    onChange={(event) => set('received_at', event.target.value)}
                />
                {errors.received_at ? (
                    <span className="text-xs text-destructive">
                        {errors.received_at}
                    </span>
                ) : null}
            </FilterField>
            <FilterField label="Reference (optional)">
                <Input
                    value={values.reference}
                    onChange={(event) => set('reference', event.target.value)}
                />
            </FilterField>
            <div className="sm:col-span-2 lg:col-span-3">
                <Button type="submit" size="sm" disabled={processing}>
                    Record payout
                </Button>
            </div>
        </form>
    );
}

AdsReconciliation.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Advertising', href: '/admin/ads' },
        { title: 'Reconciliation', href: '/admin/ads/reconciliation' },
    ],
};
