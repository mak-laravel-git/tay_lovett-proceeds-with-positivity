import { Head, Link, router } from '@inertiajs/react';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatusBadge,
} from '@/components/admin/admin-page';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import type { Run } from '@/pages/admin/workflow/runs';

type Props = {
    run: Run & {
        quote: { text: string; status: string } | null;
        can_unlock: boolean;
        can_cancel: boolean;
        rules: Record<string, unknown>;
    };
    events: {
        id: number;
        at: string | null;
        category: string;
        event: string;
        level: string;
        context: Record<string, unknown> | null;
    }[];
    attempts: {
        id: string;
        step: string;
        status: string;
        ad_unit_path: string | null;
        error_detail: string | null;
        at: string | null;
    }[];
    surveys: {
        id: string;
        provider: string;
        status: string;
        reward_status: string;
        at: string | null;
    }[];
};

export default function WorkflowRunShow({
    run,
    events,
    attempts,
    surveys,
}: Props) {
    const act = (action: 'unlock' | 'cancel', question: string) => {
        if (window.confirm(question)) {
            router.post(
                `/admin/workflow/runs/${run.id}/${action}`,
                {},
                { preserveScroll: true },
            );
        }
    };

    return (
        <>
            <Head title="Workflow run" />
            <AdminPage
                title={`Run by ${run.user?.name ?? 'a deleted user'}`}
                description={`${run.category ?? 'Unknown category'} · started ${formatDateTime(run.created_at)}`}
                actions={
                    <>
                        <StatusBadge value={run.state} />
                        {run.can_unlock ? (
                            <Button
                                onClick={() =>
                                    act(
                                        'unlock',
                                        'Release this quote without the last ad?',
                                    )
                                }
                            >
                                Release quote
                            </Button>
                        ) : null}
                        {run.can_cancel ? (
                            <Button
                                variant="outline"
                                onClick={() =>
                                    act('cancel', 'Cancel this run?')
                                }
                            >
                                Cancel run
                            </Button>
                        ) : null}
                    </>
                }
            >
                {run.quote ? (
                    <AdminNotice>
                        <b>Quote ({run.quote.status.replaceAll('_', ' ')}):</b>{' '}
                        {run.quote.text}
                    </AdminNotice>
                ) : null}

                <AdminSectionCard title="Rewarded ads">
                    {attempts.length === 0 ? (
                        <AdminEmpty>No ad was requested.</AdminEmpty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>Step</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead>Detail</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {attempts.map((attempt) => (
                                    <TableRow key={attempt.id}>
                                        <TableCell>
                                            {formatDateTime(attempt.at)}
                                        </TableCell>
                                        <TableCell>{attempt.step}</TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                value={attempt.status}
                                            />
                                        </TableCell>
                                        <TableCell className="font-mono text-xs">
                                            {attempt.ad_unit_path ?? '—'}
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
                </AdminSectionCard>

                <AdminSectionCard title="Surveys">
                    {surveys.length === 0 ? (
                        <AdminEmpty>No survey was started.</AdminEmpty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Started</TableHead>
                                    <TableHead>Provider</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Reward</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {surveys.map((survey) => (
                                    <TableRow key={survey.id}>
                                        <TableCell>
                                            {formatDateTime(survey.at)}
                                        </TableCell>
                                        <TableCell>{survey.provider}</TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                value={survey.status}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                value={survey.reward_status}
                                            />
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    href={`/admin/ads/activity/${survey.id}`}
                                                >
                                                    Transaction
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </AdminSectionCard>

                <AdminSectionCard
                    title="Timeline"
                    description="Everything recorded for this run, oldest first."
                >
                    {events.length === 0 ? (
                        <AdminEmpty>
                            No log lines remain for this run.
                        </AdminEmpty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>Area</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Detail</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {events.map((event) => (
                                    <TableRow key={event.id}>
                                        <TableCell>
                                            {formatDateTime(event.at)}
                                        </TableCell>
                                        <TableCell>{event.category}</TableCell>
                                        <TableCell>
                                            {event.event.replaceAll('_', ' ')}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge value={event.level} />
                                        </TableCell>
                                        <TableCell className="max-w-md font-mono text-xs break-all whitespace-normal">
                                            {event.context
                                                ? JSON.stringify(event.context)
                                                : '—'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </AdminSectionCard>

                <AdminSectionCard
                    title="Rules used"
                    description="The settings in force when this run started."
                >
                    <pre className="overflow-auto rounded-md bg-[#f8fbfe] p-3 text-xs">
                        {JSON.stringify(run.rules, null, 2)}
                    </pre>
                </AdminSectionCard>
            </AdminPage>
        </>
    );
}

WorkflowRunShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Quote workflow', href: '/admin/workflow' },
        { title: 'Runs', href: '/admin/workflow/runs' },
    ],
};
