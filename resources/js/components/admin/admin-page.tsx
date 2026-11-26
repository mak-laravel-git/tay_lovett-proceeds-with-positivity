import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

export function formatCents(value: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value / 100);
}

export function AdminPage({
    title,
    description,
    actions,
    children,
}: {
    title: string;
    description?: string;
    actions?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="flex h-full flex-1 flex-col gap-4 p-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-[#0a1018]">
                        {title}
                    </h1>
                    {description ? (
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                            {description}
                        </p>
                    ) : null}
                </div>
                {actions ? (
                    <div className="flex flex-wrap items-center gap-2">
                        {actions}
                    </div>
                ) : null}
            </div>
            {children}
        </div>
    );
}

export function AdminSectionCard({
    title,
    description,
    actions,
    children,
    className,
}: {
    title: string;
    description?: string;
    actions?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <Card
            className={cn(
                'gap-0 overflow-hidden border-[#76d8f8]/35 bg-white py-0 shadow-sm',
                className,
            )}
        >
            <div className="h-1.5 bg-gradient-to-r from-[#76d8f8] to-[#ed2948]" />
            <CardHeader className="gap-3 pt-4 pb-3">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <CardTitle>{title}</CardTitle>
                        {description ? (
                            <CardDescription>{description}</CardDescription>
                        ) : null}
                    </div>
                    {actions}
                </div>
            </CardHeader>
            <CardContent className="pb-4">{children}</CardContent>
        </Card>
    );
}

export function AdminNotice({
    children,
    tone = 'info',
}: {
    children: ReactNode;
    tone?: 'info' | 'warn';
}) {
    return (
        <div
            className={cn(
                'rounded-xl border px-3 py-2.5 text-sm',
                tone === 'info' &&
                    'border-[#9cdcec] bg-[#e7f8ff] text-[#0f6f8f]',
                tone === 'warn' &&
                    'border-[#ed2948]/25 bg-[#ffedf0] text-[#a1283c]',
            )}
        >
            {children}
        </div>
    );
}

export function AdminEmpty({ children }: { children: ReactNode }) {
    return (
        <div className="rounded-xl border border-dashed border-[#d4e7ed] bg-[#f8fbfe] px-4 py-10 text-center text-sm text-muted-foreground">
            {children}
        </div>
    );
}

const statusStyles: Record<string, string> = {
    pending_review: 'border-amber-200 bg-amber-50 text-amber-800',
    pending: 'border-amber-200 bg-amber-50 text-amber-800',
    approved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    published: 'border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]',
    rejected: 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
    draft: 'border-[#d4e7ed] bg-white text-[#526475]',
    paid: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    healthy: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    unhealthy: 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
    invalid: 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
    unknown: 'border-[#d4e7ed] bg-white text-[#526475]',
    ad_presented: 'border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]',
    ad_exited: 'border-[#d4e7ed] bg-white text-[#526475]',
    credit: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    debit: 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
    reserve: 'border-amber-200 bg-amber-50 text-amber-800',
    release: 'border-[#d4e7ed] bg-white text-[#526475]',
    // Quote workflow: run states, rewarded ad attempts, log levels.
    awaiting_unlock: 'border-amber-200 bg-amber-50 text-amber-800',
    ad_before_survey: 'border-amber-200 bg-amber-50 text-amber-800',
    survey: 'border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]',
    generate: 'border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]',
    generating: 'border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]',
    ad_before_publish: 'border-amber-200 bg-amber-50 text-amber-800',
    completed: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    cancelled: 'border-[#d4e7ed] bg-white text-[#526475]',
    issued: 'border-[#d4e7ed] bg-white text-[#526475]',
    ready: 'border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]',
    shown: 'border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]',
    granted: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    dismissed: 'border-amber-200 bg-amber-50 text-amber-800',
    superseded: 'border-[#d4e7ed] bg-white text-[#526475]',
    info: 'border-[#d4e7ed] bg-white text-[#526475]',
    warning: 'border-amber-200 bg-amber-50 text-amber-800',
    // Ads & surveys: transaction lifecycle, reward, provider calls, callbacks, reconciliation.
    loaded: 'border-[#d4e7ed] bg-white text-[#526475]',
    started: 'border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]',
    returned: 'border-amber-200 bg-amber-50 text-amber-800',
    failed: 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
    expired: 'border-[#d4e7ed] bg-white text-[#526475]',
    credited: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    not_eligible: 'border-[#d4e7ed] bg-white text-[#526475]',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    empty: 'border-amber-200 bg-amber-50 text-amber-800',
    ineligible: 'border-amber-200 bg-amber-50 text-amber-800',
    skipped: 'border-[#d4e7ed] bg-white text-[#526475]',
    duplicate: 'border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]',
    recorded: 'border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f]',
    invalid_signature: 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
    invalid_token: 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
    unmatched: 'border-amber-200 bg-amber-50 text-amber-800',
    unsuccessful_outcome: 'border-[#d4e7ed] bg-white text-[#526475]',
    not_rewarded: 'border-[#d4e7ed] bg-white text-[#526475]',
    error: 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
    matched: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    discrepancy: 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
    unavailable: 'border-amber-200 bg-amber-50 text-amber-800',
    missing_local: 'border-amber-200 bg-amber-50 text-amber-800',
    missing_provider: 'border-amber-200 bg-amber-50 text-amber-800',
    amount_mismatch: 'border-[#ed2948]/30 bg-[#ffedf0] text-[#ed2948]',
};

export function StatusBadge({
    value,
    label,
}: {
    value: string;
    label?: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn(
                'capitalize',
                statusStyles[value] ??
                    'border-[#d4e7ed] bg-white text-[#526475]',
            )}
        >
            {(label ?? value).replaceAll('_', ' ')}
        </Badge>
    );
}

export function StatTile({
    label,
    value,
    hint,
    tone = 'blue',
}: {
    label: string;
    value: string;
    hint?: string;
    tone?: 'blue' | 'red' | 'navy' | 'cyan';
}) {
    const tones = {
        blue: 'border-[#76d8f8]/60 bg-[#eef9fd]',
        red: 'border-[#ed2948]/45 bg-[#fff1f4]',
        navy: 'border-[#243846] bg-[#0a1018] text-white',
        cyan: 'border-[#76d8f8]/50 bg-gradient-to-br from-[#e7f7ff] to-[#fff0f3]',
    } as const;

    return (
        <div className={cn('rounded-2xl border p-3 shadow-sm', tones[tone])}>
            <p
                className={cn(
                    'text-xs font-semibold tracking-wide uppercase',
                    tone === 'navy' ? 'text-[#9ec3d1]' : 'text-[#526475]',
                )}
            >
                {label}
            </p>
            <p
                className={cn(
                    'mt-2 text-xl font-semibold tabular-nums',
                    tone === 'navy' ? 'text-white' : 'text-[#0a1018]',
                    tone === 'red' && 'text-[#ed2948]',
                )}
            >
                {value}
            </p>
            {hint ? (
                <p
                    className={cn(
                        'mt-0.5 text-xs',
                        tone === 'navy' ? 'text-[#8fb0bd]' : 'text-muted-foreground',
                    )}
                >
                    {hint}
                </p>
            ) : null}
        </div>
    );
}
