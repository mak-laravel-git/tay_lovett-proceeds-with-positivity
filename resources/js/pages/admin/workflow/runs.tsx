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
import type { Paginated, PersonRef, SelectOption } from '@/types/ads';

export type Run = {
    id: string;
    user: PersonRef | null;
    category: string | null;
    state: string;
    ad1_result: string | null;
    survey_result: string | null;
    ad2_result: string | null;
    created_at: string | null;
    completed_at: string | null;
};

export default function WorkflowRuns({
    runs,
    filters,
    states,
}: {
    runs: Paginated<Run>;
    filters: Record<string, string>;
    states: SelectOption[];
}) {
    const filter = useFilters('/admin/workflow/runs', {
        state: filters.state ?? '',
        user: filters.user ?? '',
    });
    const step = (result: string | null) =>
        result ? result.replaceAll('_', ' ') : '—';

    return (
        <>
            <Head title="Workflow runs" />
            <AdminPage
                title="Workflow runs"
                description="Each user's pass through the quote flow. Open one to see its timeline or release a waiting quote."
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/admin/workflow">Workflow settings</Link>
                    </Button>
                }
            >
                <AdminSectionCard
                    title="Runs"
                    description={`${runs.total} in total`}
                >
                    <FilterBar
                        onApply={filter.apply}
                        onReset={filter.reset}
                        active={filter.active}
                    >
                        <SelectFilter
                            label="State"
                            value={filter.values.state}
                            options={states}
                            onChange={(value) => filter.set('state', value)}
                        />
                        <TextFilter
                            label="User name or email"
                            value={filter.values.user}
                            onChange={(value) => filter.set('user', value)}
                        />
                    </FilterBar>
                    {runs.data.length === 0 ? (
                        <AdminEmpty>No runs match.</AdminEmpty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Started</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>State</TableHead>
                                    <TableHead>Ad 1</TableHead>
                                    <TableHead>Survey</TableHead>
                                    <TableHead>Ad 2</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {runs.data.map((run) => (
                                    <TableRow key={run.id}>
                                        <TableCell>
                                            {formatDateTime(run.created_at)}
                                        </TableCell>
                                        <TableCell>
                                            {run.user?.name ?? 'Deleted user'}
                                            <div className="text-xs text-muted-foreground">
                                                {run.user?.email}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge value={run.state} />
                                        </TableCell>
                                        <TableCell>
                                            {step(run.ad1_result)}
                                        </TableCell>
                                        <TableCell>
                                            {step(run.survey_result)}
                                        </TableCell>
                                        <TableCell>
                                            {step(run.ad2_result)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    href={`/admin/workflow/runs/${run.id}`}
                                                >
                                                    Open
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                    <Pagination page={runs} />
                </AdminSectionCard>
            </AdminPage>
        </>
    );
}

WorkflowRuns.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Quote workflow', href: '/admin/workflow' },
        { title: 'Runs', href: '/admin/workflow/runs' },
    ],
};
