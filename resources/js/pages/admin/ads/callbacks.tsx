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
import type { Paginated, SelectOption } from '@/types/ads';

type Callback = {
    id: number;
    received_at: string;
    provider: string;
    transaction_id: string | null;
    activity_id: string | null;
    result: string;
    result_label: string;
    signature_valid: boolean;
    note: string | null;
    revenue_usd: string;
    ip: string | null;
    payload: Record<string, unknown> | null;
};

type Props = {
    callbacks: Paginated<Callback>;
    filters: Partial<Record<'result' | 'search', string>>;
    results: SelectOption[];
};

export default function AdsCallbacks({ callbacks, filters, results }: Props) {
    const form = useFilters('/admin/ads/callbacks', {
        result: filters.result ?? '',
        search: filters.search ?? '',
    });

    return (
        <>
            <Head title="Provider callbacks" />
            <AdminPage
                title="Provider callbacks"
                description="Every confirmation a provider sent us, valid or not. Invalid and unmatched ones are kept for audit and never award points."
            >
                <AdminNotice>
                    Revenue shown is only counted for callbacks whose signature
                    verified; it is 0 for rejected requests.
                </AdminNotice>
                <AdminSectionCard title="Received callbacks">
                    <FilterBar
                        onApply={form.apply}
                        onReset={form.reset}
                        active={form.active}
                    >
                        <SelectFilter
                            label="Result"
                            value={form.values.result}
                            onChange={(value) => form.set('result', value)}
                            options={results}
                        />
                        <TextFilter
                            label="Transaction or activity ID"
                            value={form.values.search}
                            onChange={(value) => form.set('search', value)}
                        />
                    </FilterBar>

                    {callbacks.data.length === 0 ? (
                        <AdminEmpty>
                            No callbacks match these filters.
                        </AdminEmpty>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Received</TableHead>
                                        <TableHead>Provider</TableHead>
                                        <TableHead>
                                            Transaction (cpid)
                                        </TableHead>
                                        <TableHead>Result</TableHead>
                                        <TableHead>Signature</TableHead>
                                        <TableHead className="text-right">
                                            Revenue
                                        </TableHead>
                                        <TableHead>From</TableHead>
                                        <TableHead>Details</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {callbacks.data.map((callback) => (
                                        <TableRow key={callback.id}>
                                            <TableCell className="whitespace-nowrap">
                                                {formatDateTime(
                                                    callback.received_at,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {callback.provider}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs">
                                                {callback.activity_id ? (
                                                    <Link
                                                        href={`/admin/ads/activity/${callback.activity_id}`}
                                                        className="text-[#0f6f8f] hover:underline"
                                                    >
                                                        {callback.transaction_id ??
                                                            'open'}
                                                    </Link>
                                                ) : (
                                                    (callback.transaction_id ??
                                                    '—')
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    value={callback.result}
                                                    label={
                                                        callback.result_label
                                                    }
                                                />
                                                {callback.note ? (
                                                    <div className="mt-0.5 text-xs text-muted-foreground">
                                                        {callback.note.replaceAll(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </div>
                                                ) : null}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    value={
                                                        callback.signature_valid
                                                            ? 'matched'
                                                            : 'invalid_signature'
                                                    }
                                                    label={
                                                        callback.signature_valid
                                                            ? 'valid'
                                                            : 'invalid'
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {usd(callback.revenue_usd)}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs">
                                                {callback.ip ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {callback.payload ? (
                                                    <details>
                                                        <summary className="cursor-pointer text-xs font-medium text-[#0f6f8f]">
                                                            Parameters
                                                        </summary>
                                                        <pre className="mt-2 max-w-sm overflow-x-auto rounded-lg bg-[#0a1018] p-2 text-xs text-[#cfe9f3]">
                                                            {JSON.stringify(
                                                                callback.payload,
                                                                null,
                                                                2,
                                                            )}
                                                        </pre>
                                                    </details>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <Pagination page={callbacks} />
                        </>
                    )}
                </AdminSectionCard>
            </AdminPage>
        </>
    );
}

AdsCallbacks.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Advertising', href: '/admin/ads' },
        { title: 'Callbacks', href: '/admin/ads/callbacks' },
    ],
};
