import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Check, CircleDashed, Minus, X } from 'lucide-react';
import {
    AdminNotice,
    AdminPage,
    AdminSectionCard,
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
import { formatDateTime, usd } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { PersonRef } from '@/types/ads';

type TimelineStep = {
    key: string;
    label: string;
    at: string | null;
    state: 'done' | 'pending' | 'skipped' | 'failed';
    detail: string | null;
};

type Activity = {
    id: string;
    source: string;
    user: PersonRef | null;
    provider: string;
    provider_user_id: string | null;
    survey: string | null;
    survey_length_minutes: number | null;
    survey_category: string | null;
    provider_transaction_id: string | null;
    status: string;
    reward_status: string;
    reward_note: string | null;
    provider_status: string | null;
    survey_outcome: number | null;
    payout_type: number | null;
    provider_payout_amount: number | null;
    payout_currency: string | null;
    publisher_revenue_usd: string;
    publisher_revenue_micros: number;
    awarded_points: number;
    callback_verification_status: string;
    callback_payload: Record<string, unknown> | null;
    error_code: string | null;
    error_message: string | null;
    created_at: string | null;
    updated_at: string | null;
};

type Callback = {
    id: number;
    received_at: string;
    result: string;
    result_label: string;
    signature_valid: boolean;
    note: string | null;
    revenue_usd: string;
    ip: string | null;
    payload: Record<string, unknown> | null;
};

type Props = {
    activity: Activity;
    timeline: TimelineStep[];
    callbacks: Callback[];
    ledger: {
        id: number;
        points: number;
        key: string;
        description: string;
        at: string | null;
    }[];
    provider_calls: {
        provider: string;
        outcome: string;
        error: string | null;
        http_status: number | null;
        latency_ms: number | null;
        position: number;
    }[];
    timezone: string;
};

const stepStyles: Record<TimelineStep['state'], string> = {
    done: 'border-emerald-300 bg-emerald-50 text-emerald-700',
    pending: 'border-[#d4e7ed] bg-white text-[#9ab0bd]',
    skipped: 'border-amber-300 bg-amber-50 text-amber-700',
    failed: 'border-[#ed2948]/40 bg-[#ffedf0] text-[#ed2948]',
};

function StepIcon({ state }: { state: TimelineStep['state'] }) {
    const Icon =
        state === 'done'
            ? Check
            : state === 'failed'
              ? X
              : state === 'skipped'
                ? Minus
                : CircleDashed;

    return <Icon className="size-4" aria-hidden />;
}

function Detail({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="min-w-0">
            <dt className="text-xs font-semibold tracking-wide text-[#526475] uppercase">
                {label}
            </dt>
            <dd className="mt-0.5 text-sm break-words text-[#0a1018]">
                {children ?? '—'}
            </dd>
        </div>
    );
}

export default function AdsActivityShow({
    activity,
    timeline,
    callbacks,
    ledger,
    provider_calls,
    timezone,
}: Props) {
    return (
        <>
            <Head title={`Survey ${activity.survey ?? activity.id}`} />
            <AdminPage
                title={`Survey ${activity.survey ?? '(unknown)'}`}
                description="Who completed it, when, what the provider paid, and what the user received."
                actions={
                    <Link
                        href="/admin/ads/activity"
                        className="inline-flex items-center gap-1 text-sm font-medium text-[#0f6f8f] hover:underline"
                    >
                        <ArrowLeft className="size-4" /> All activity
                    </Link>
                }
            >
                <div className="flex flex-wrap items-center gap-2">
                    <StatusBadge value={activity.status} />
                    <StatusBadge
                        value={activity.reward_status}
                        label={`Reward ${activity.reward_status.replaceAll('_', ' ')}`}
                    />
                    <StatusBadge
                        value={
                            activity.callback_verification_status === 'verified'
                                ? 'matched'
                                : 'pending'
                        }
                        label={
                            activity.callback_verification_status === 'verified'
                                ? 'Postback verified'
                                : 'No verified postback'
                        }
                    />
                    {activity.source === 'postback_only' ? (
                        <StatusBadge
                            value="unmatched"
                            label="Postback only — no survey start recorded"
                        />
                    ) : null}
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <AdminSectionCard
                        title="Publisher revenue"
                        description="What the provider reported earning the publisher. Not cash received."
                    >
                        <p className="text-3xl font-semibold tabular-nums">
                            {usd(activity.publisher_revenue_usd)}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Provider payout to user:{' '}
                            {activity.provider_payout_amount ?? '—'}{' '}
                            {activity.payout_currency ?? ''} (not used for the
                            user’s reward)
                        </p>
                    </AdminSectionCard>
                    <AdminSectionCard
                        title="User reward"
                        description="Points set by the admin reward rule."
                    >
                        <p className="text-3xl font-semibold tabular-nums">
                            {activity.awarded_points} pts
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {activity.reward_note
                                ? `Note: ${activity.reward_note.replaceAll('_', ' ')}`
                                : 'Credited through the point ledger.'}
                        </p>
                    </AdminSectionCard>
                    <AdminSectionCard title="Who and what">
                        <dl className="grid gap-3">
                            <Detail label="User">
                                {activity.user ? (
                                    <Link
                                        href={`/admin/users/${activity.user.id}`}
                                        className="text-[#0f6f8f] hover:underline"
                                    >
                                        {activity.user.name} ·{' '}
                                        {activity.user.email}
                                    </Link>
                                ) : (
                                    'Unknown user'
                                )}
                            </Detail>
                            <Detail label="Provider">
                                {activity.provider}
                            </Detail>
                            <Detail label="Survey">
                                {activity.survey}
                                {activity.survey_length_minutes
                                    ? ` · ${activity.survey_length_minutes} min`
                                    : ''}
                                {activity.survey_category
                                    ? ` · ${activity.survey_category}`
                                    : ''}
                            </Detail>
                        </dl>
                    </AdminSectionCard>
                </div>

                <AdminSectionCard
                    title="Timeline"
                    description={`Times in ${timezone}. A step that has not happened is left blank, never guessed.`}
                >
                    <ol className="grid gap-0">
                        {timeline.map((step, index) => (
                            <li key={step.key} className="flex gap-3">
                                <div className="flex flex-col items-center">
                                    <span
                                        className={cn(
                                            'flex size-7 shrink-0 items-center justify-center rounded-full border',
                                            stepStyles[step.state],
                                        )}
                                    >
                                        <StepIcon state={step.state} />
                                    </span>
                                    {index < timeline.length - 1 ? (
                                        <span className="w-px flex-1 bg-[#d4e7ed]" />
                                    ) : null}
                                </div>
                                <div className="pb-5">
                                    <p
                                        className={cn(
                                            'text-sm font-medium',
                                            step.state === 'pending' &&
                                                'text-[#9ab0bd]',
                                        )}
                                    >
                                        {step.label}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {step.at
                                            ? formatDateTime(step.at, timezone)
                                            : step.state === 'pending'
                                              ? 'Not yet'
                                              : '—'}
                                    </p>
                                    {step.detail ? (
                                        <p className="mt-0.5 text-xs text-[#526475]">
                                            {step.detail}
                                        </p>
                                    ) : null}
                                </div>
                            </li>
                        ))}
                    </ol>
                </AdminSectionCard>

                <AdminSectionCard
                    title="Transaction identifiers"
                    description="For audit and for matching against the provider’s own records."
                >
                    <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Detail label="Activity ID">{activity.id}</Detail>
                        <Detail label="Provider transaction ID (cpid)">
                            {activity.provider_transaction_id}
                        </Detail>
                        <Detail label="Provider user ID">
                            {activity.provider_user_id}
                        </Detail>
                        <Detail label="Survey outcome">
                            {activity.survey_outcome === null
                                ? null
                                : `${activity.survey_outcome} (${
                                      activity.survey_outcome === 1
                                          ? 'complete'
                                          : activity.survey_outcome === 2
                                            ? 'disqualified'
                                            : 'over quota'
                                  })`}
                        </Detail>
                        <Detail label="Payout type">
                            {activity.payout_type}
                        </Detail>
                        <Detail label="Redirect status (informational)">
                            {activity.provider_status}
                        </Detail>
                        <Detail label="Created">
                            {formatDateTime(activity.created_at, timezone)}
                        </Detail>
                        <Detail label="Last updated">
                            {formatDateTime(activity.updated_at, timezone)}
                        </Detail>
                        <Detail label="Revenue (micros)">
                            {activity.publisher_revenue_micros}
                        </Detail>
                    </dl>
                    {activity.error_code || activity.error_message ? (
                        <div className="mt-4">
                            <AdminNotice tone="warn">
                                {activity.error_code} {activity.error_message}
                            </AdminNotice>
                        </div>
                    ) : null}
                </AdminSectionCard>

                <AdminSectionCard
                    title="Provider callbacks"
                    description="Every callback received for this transaction, including duplicates."
                >
                    {callbacks.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No callback has been received for this transaction.
                        </p>
                    ) : (
                        <div className="grid gap-3">
                            {callbacks.map((callback) => (
                                <div
                                    key={callback.id}
                                    className="rounded-xl border border-[#e2eef2] p-3"
                                >
                                    <div className="flex flex-wrap items-center gap-2 text-sm">
                                        <StatusBadge
                                            value={callback.result}
                                            label={callback.result_label}
                                        />
                                        <span>
                                            {formatDateTime(
                                                callback.received_at,
                                                timezone,
                                            )}
                                        </span>
                                        <span className="text-muted-foreground">
                                            signature{' '}
                                            {callback.signature_valid
                                                ? 'valid'
                                                : 'invalid'}{' '}
                                            · revenue{' '}
                                            {usd(callback.revenue_usd)}
                                            {callback.ip
                                                ? ` · ${callback.ip}`
                                                : ''}
                                        </span>
                                    </div>
                                    {callback.note ? (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {callback.note.replaceAll('_', ' ')}
                                        </p>
                                    ) : null}
                                    {callback.payload ? (
                                        <details className="mt-2">
                                            <summary className="cursor-pointer text-xs font-medium text-[#0f6f8f]">
                                                Parameters received
                                            </summary>
                                            <pre className="mt-2 overflow-x-auto rounded-lg bg-[#0a1018] p-3 text-xs text-[#cfe9f3]">
                                                {JSON.stringify(
                                                    callback.payload,
                                                    null,
                                                    2,
                                                )}
                                            </pre>
                                        </details>
                                    ) : null}
                                </div>
                            ))}
                        </div>
                    )}
                </AdminSectionCard>

                <div className="grid gap-4 lg:grid-cols-2">
                    <AdminSectionCard title="Point ledger entries">
                        {ledger.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No points have been credited for this
                                transaction.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Points</TableHead>
                                        <TableHead>Ledger key</TableHead>
                                        <TableHead>When</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {ledger.map((entry) => (
                                        <TableRow key={entry.id}>
                                            <TableCell className="tabular-nums">
                                                +{entry.points}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs">
                                                {entry.key}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {formatDateTime(
                                                    entry.at,
                                                    timezone,
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </AdminSectionCard>

                    <AdminSectionCard
                        title="Provider calls for this survey list"
                        description="The fallback chain used when the user asked for surveys."
                    >
                        {provider_calls.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Not recorded for this transaction.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>#</TableHead>
                                        <TableHead>Provider</TableHead>
                                        <TableHead>Outcome</TableHead>
                                        <TableHead>Detail</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {provider_calls.map((call) => (
                                        <TableRow key={call.position}>
                                            <TableCell>
                                                {call.position}
                                            </TableCell>
                                            <TableCell>
                                                {call.provider}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    value={call.outcome}
                                                />
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                {[
                                                    call.http_status
                                                        ? `HTTP ${call.http_status}`
                                                        : null,
                                                    call.latency_ms !== null
                                                        ? `${call.latency_ms} ms`
                                                        : null,
                                                    call.error,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ') || '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </AdminSectionCard>
                </div>
            </AdminPage>
        </>
    );
}

AdsActivityShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Advertising', href: '/admin/ads' },
        { title: 'Survey activity', href: '/admin/ads/activity' },
        { title: 'Transaction', href: '#' },
    ],
};
