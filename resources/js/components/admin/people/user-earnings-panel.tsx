import { router } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight, Minus } from 'lucide-react';
import type { EarningsMonth, LedgerRow } from '@/components/admin/people/types';
import { Badge } from '@/components/ui/badge';
import { MonthYearPicker } from '@/components/ui/month-year-picker';
import { cn } from '@/lib/utils';

function cents(value: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value / 100);
}

function progressWidth(current: number, previous: number): number {
    const max = Math.max(Math.abs(current), Math.abs(previous), 1);

    return Math.min(100, Math.round((Math.abs(current) / max) * 100));
}

function ChangeBadge({
    percent,
}: {
    percent: number | null;
}) {
    if (percent === null) {
        return (
            <Badge
                variant="outline"
                className="border-[#d4e7ed] bg-white text-[#526475]"
            >
                New
            </Badge>
        );
    }

    if (percent === 0) {
        return (
            <Badge
                variant="outline"
                className="gap-1 border-[#d4e7ed] bg-white text-[#526475]"
            >
                <Minus className="size-3" />
                0%
            </Badge>
        );
    }

    const up = percent > 0;

    return (
        <Badge
            variant="outline"
            className={cn(
                'gap-1',
                up
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                    : 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
            )}
        >
            {up ? (
                <ArrowUpRight className="size-3" />
            ) : (
                <ArrowDownRight className="size-3" />
            )}
            {Math.abs(percent)}%
        </Badge>
    );
}

function CompareMetric({
    label,
    current,
    previous,
    percent,
    format = 'money',
    tone,
}: {
    label: string;
    current: number;
    previous: number;
    percent: number | null;
    format?: 'money' | 'count';
    tone: 'blue' | 'red' | 'navy' | 'cyan';
}) {
    const tones = {
        blue: 'bg-[#76d8f8]',
        red: 'bg-[#ed2948]',
        navy: 'bg-[#0a1018]',
        cyan: 'bg-gradient-to-r from-[#76d8f8] to-[#ed2948]',
    };

    const display =
        format === 'money' ? cents(current) : current.toLocaleString();
    const previousDisplay =
        format === 'money' ? cents(previous) : previous.toLocaleString();

    return (
        <div className="rounded-2xl border border-[#d4e7ed] bg-white p-3 shadow-sm">
            <div className="flex items-start justify-between gap-2">
                <p className="text-xs font-semibold tracking-wide text-[#526475] uppercase">
                    {label}
                </p>
                <ChangeBadge percent={percent} />
            </div>
            <p className="mt-2 text-xl font-semibold tabular-nums text-[#0a1018]">
                {display}
            </p>
            <p className="mt-0.5 text-xs text-muted-foreground">
                Prev: {previousDisplay}
            </p>
            <div className="mt-3 h-2 overflow-hidden rounded-full bg-[#e7f3f7]">
                <div
                    className={cn('h-full rounded-full transition-all', tones[tone])}
                    style={{ width: `${progressWidth(current, previous)}%` }}
                />
            </div>
        </div>
    );
}

type UserEarningsPanelProps = {
    showUrl: string;
    wallet: {
        pending_cents: number;
        available_cents: number;
        reserved_cents: number;
        lifetime_credited_cents: number;
    };
    earnings: EarningsMonth;
};

export function UserEarningsPanel({
    showUrl,
    wallet,
    earnings,
}: UserEarningsPanelProps) {
    function changeMonth(month: string) {
        router.get(
            showUrl,
            { month },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    }

    return (
        <div className="grid gap-4">
            <section className="rounded-2xl border border-[#76d8f8]/40 bg-gradient-to-br from-[#eef9fd] to-white p-4 shadow-sm">
                <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-sm font-semibold text-[#0a1018]">
                            Earnings & wallet
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            Live balances plus {earnings.label} vs{' '}
                            {earnings.previous_label}
                        </p>
                    </div>
                    <div className="flex items-center gap-2 text-xs font-medium text-[#41516b]">
                        <span>Month</span>
                        <MonthYearPicker
                            value={earnings.month}
                            onChange={changeMonth}
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <BalanceStat
                        label="Available"
                        value={cents(wallet.available_cents)}
                        tone="blue"
                    />
                    <BalanceStat
                        label="Lifetime"
                        value={cents(wallet.lifetime_credited_cents)}
                        tone="red"
                    />
                    <BalanceStat
                        label="Pending"
                        value={cents(wallet.pending_cents)}
                        tone="navy"
                    />
                    <BalanceStat
                        label="Reserved"
                        value={cents(wallet.reserved_cents)}
                        tone="cyan"
                    />
                </div>
            </section>

            <section className="rounded-2xl border border-[#d4e7ed] bg-white p-4 shadow-sm">
                <div className="mb-3 flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 className="text-sm font-semibold text-[#0a1018]">
                            Monthly performance
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            Progress vs previous month
                        </p>
                    </div>
                    <Badge
                        variant="outline"
                        className="border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]"
                    >
                        {earnings.label}
                    </Badge>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <CompareMetric
                        label="Credited"
                        current={earnings.current.credited_cents}
                        previous={earnings.previous.credited_cents}
                        percent={earnings.change.credited_percent}
                        tone="blue"
                    />
                    <CompareMetric
                        label="Debited"
                        current={earnings.current.debited_cents}
                        previous={earnings.previous.debited_cents}
                        percent={earnings.change.debited_percent}
                        tone="red"
                    />
                    <CompareMetric
                        label="Net"
                        current={earnings.current.net_cents}
                        previous={earnings.previous.net_cents}
                        percent={earnings.change.net_percent}
                        tone="navy"
                    />
                    <CompareMetric
                        label="Entries"
                        current={earnings.current.entries}
                        previous={earnings.previous.entries}
                        percent={earnings.change.entries_percent}
                        format="count"
                        tone="cyan"
                    />
                </div>
            </section>

            <MonthLedger rows={earnings.month_ledger} monthLabel={earnings.label} />
        </div>
    );
}

function BalanceStat({
    label,
    value,
    tone,
}: {
    label: string;
    value: string;
    tone: 'blue' | 'red' | 'navy' | 'cyan';
}) {
    const tones = {
        blue: 'border-[#76d8f8]/40 bg-[#eef9fd]',
        red: 'border-[#ed2948]/30 bg-[#fff1f4]',
        navy: 'border-[#0a1018]/20 bg-[#0a1018] text-white',
        cyan: 'border-[#76d8f8]/40 bg-gradient-to-br from-[#e7f7ff] to-[#fff0f3]',
    };

    return (
        <div className={cn('rounded-xl border p-3', tones[tone])}>
            <p
                className={cn(
                    'text-[11px] font-medium uppercase tracking-wide',
                    tone === 'navy' ? 'text-[#9ec3d1]' : 'text-muted-foreground',
                )}
            >
                {label}
            </p>
            <p className="mt-1 text-base font-semibold tabular-nums">{value}</p>
        </div>
    );
}

function MonthLedger({
    rows,
    monthLabel,
}: {
    rows: LedgerRow[];
    monthLabel: string;
}) {
    return (
        <section className="overflow-hidden rounded-2xl border border-[#d4e7ed] bg-white shadow-sm">
            <div className="flex items-center justify-between gap-2 border-b border-[#e7f3f7] px-4 py-3">
                <h2 className="text-sm font-semibold">Month ledger</h2>
                <span className="text-xs text-muted-foreground">{monthLabel}</span>
            </div>
            {rows.length === 0 ? (
                <div className="px-4 py-8 text-center text-sm text-muted-foreground">
                    No ledger activity in this month.
                </div>
            ) : (
                <table className="w-full text-left text-sm">
                    <thead className="bg-[#f7fcff] text-muted-foreground">
                        <tr>
                            <th className="px-4 py-2 font-medium">Type</th>
                            <th className="px-4 py-2 font-medium">Amount</th>
                            <th className="px-4 py-2 font-medium">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.id} className="border-t">
                                <td className="px-4 py-2">
                                    {row.entry_type}
                                    {row.description
                                        ? ` · ${row.description}`
                                        : ''}
                                </td>
                                <td className="px-4 py-2 tabular-nums">
                                    {cents(row.amount_cents)}
                                </td>
                                <td className="px-4 py-2 text-muted-foreground">
                                    {row.created_at ?? '—'}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </section>
    );
}
