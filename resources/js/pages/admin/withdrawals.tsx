import { Form, Head } from '@inertiajs/react';
import { Check, CreditCard, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatTile,
    StatusBadge,
    formatCents,
} from '@/components/admin/admin-page';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type RequestRow = {
    id: number;
    amount_cents: number;
    status: string;
    destination_label: string;
    external_reference: string | null;
    created_at: string | null;
    user: { id: number; name: string; email: string } | null;
};

const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'paid', label: 'Paid' },
    { value: 'rejected', label: 'Rejected' },
] as const;

export default function AdminWithdrawals({
    requests,
    stats,
}: {
    requests: RequestRow[];
    stats: {
        pending: number;
        approved: number;
        paid: number;
        rejected: number;
    };
}) {
    const [status, setStatus] = useState<(typeof statusTabs)[number]['value']>(
        'all',
    );
    const [paidTarget, setPaidTarget] = useState<RequestRow | null>(null);

    const filtered = useMemo(
        () =>
            status === 'all'
                ? requests
                : requests.filter((request) => request.status === status),
        [requests, status],
    );

    return (
        <>
            <Head title="Withdrawal review" />
            <AdminPage
                title="Withdrawal review"
                description="Approve, reject, or mark withdrawals paid after an external transfer."
            >
                <AdminNotice>
                    Approving a request does not send an external transfer.
                </AdminNotice>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <StatTile
                        label="Pending"
                        value={String(stats.pending)}
                        tone="navy"
                    />
                    <StatTile
                        label="Approved"
                        value={String(stats.approved)}
                        tone="blue"
                    />
                    <StatTile
                        label="Paid"
                        value={String(stats.paid)}
                        tone="cyan"
                    />
                    <StatTile
                        label="Rejected"
                        value={String(stats.rejected)}
                        tone="red"
                    />
                </div>

                <AdminSectionCard
                    title="Requests"
                    description={`${filtered.length} request${filtered.length === 1 ? '' : 's'}`}
                >
                    <div className="mb-4 flex flex-wrap gap-2">
                        {statusTabs.map((tab) => (
                            <button
                                key={tab.value}
                                type="button"
                                onClick={() => setStatus(tab.value)}
                                className={cn(
                                    'rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors',
                                    status === tab.value
                                        ? 'border-[#ed2948] bg-[#ffedf0] text-[#ed2948]'
                                        : 'border-[#d4e7ed] bg-white text-[#526475] hover:border-[#76d8f8]',
                                )}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>

                    {filtered.length === 0 ? (
                        <AdminEmpty>No withdrawal requests here.</AdminEmpty>
                    ) : (
                        <div className="grid gap-3">
                            {filtered.map((request) => (
                                <div
                                    key={request.id}
                                    className="rounded-2xl border border-[#d4e7ed] bg-[#f8fbfe] p-4"
                                >
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-semibold text-[#0a1018]">
                                                    {request.user?.name ??
                                                        'Unknown user'}
                                                </p>
                                                <StatusBadge
                                                    value={request.status}
                                                />
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {request.user?.email}
                                            </p>
                                            <p className="mt-3 text-2xl font-semibold tabular-nums text-[#0a1018]">
                                                {formatCents(
                                                    request.amount_cents,
                                                )}
                                            </p>
                                            <p className="mt-1 text-sm text-[#41516b]">
                                                {request.destination_label}
                                            </p>
                                            {request.external_reference ? (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    Ref:{' '}
                                                    {request.external_reference}
                                                </p>
                                            ) : null}
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {request.created_at ?? ''}
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            {request.status === 'pending' ? (
                                                <Form
                                                    action={`/admin/withdrawals/${request.id}/approve`}
                                                    method="post"
                                                >
                                                    <Button
                                                        type="submit"
                                                        size="sm"
                                                        className="bg-emerald-600 text-white hover:bg-emerald-700"
                                                    >
                                                        <Check className="size-3.5" />
                                                        Approve
                                                    </Button>
                                                </Form>
                                            ) : null}
                                            {request.status === 'pending' ||
                                            request.status === 'approved' ? (
                                                <Form
                                                    action={`/admin/withdrawals/${request.id}/reject`}
                                                    method="post"
                                                >
                                                    <Button
                                                        type="submit"
                                                        size="sm"
                                                        variant="outline"
                                                        className="border-[#ed2948]/30 text-[#ed2948] hover:bg-[#ffedf0]"
                                                    >
                                                        <X className="size-3.5" />
                                                        Reject
                                                    </Button>
                                                </Form>
                                            ) : null}
                                            {request.status === 'approved' ? (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    className="border-[#76d8f8]/50 bg-white text-[#0f6f8f]"
                                                    onClick={() =>
                                                        setPaidTarget(request)
                                                    }
                                                >
                                                    <CreditCard className="size-3.5" />
                                                    Mark paid
                                                </Button>
                                            ) : null}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </AdminSectionCard>
            </AdminPage>

            <Dialog
                open={paidTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPaidTarget(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Mark withdrawal paid</DialogTitle>
                        <DialogDescription>
                            Enter the external transfer reference after the
                            money has left the platform.
                        </DialogDescription>
                    </DialogHeader>
                    {paidTarget ? (
                        <Form
                            action={`/admin/withdrawals/${paidTarget.id}/paid`}
                            method="post"
                            className="grid gap-3"
                            onSuccess={() => setPaidTarget(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="rounded-xl border border-[#d4e7ed] bg-[#f8fbfe] p-3 text-sm">
                                        <p className="font-medium text-[#0a1018]">
                                            {paidTarget.user?.name}
                                        </p>
                                        <p className="tabular-nums">
                                            {formatCents(
                                                paidTarget.amount_cents,
                                            )}
                                        </p>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="external_reference">
                                            External reference
                                        </Label>
                                        <Input
                                            id="external_reference"
                                            name="external_reference"
                                            className="bg-white"
                                        />
                                        {errors.external_reference ? (
                                            <p className="text-sm text-[#ac1832]">
                                                {errors.external_reference}
                                            </p>
                                        ) : null}
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setPaidTarget(null)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="bg-[#ed2948] text-white hover:bg-[#d61f3d]"
                                        >
                                            Confirm paid
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    ) : null}
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminWithdrawals.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Withdrawals', href: '/admin/withdrawals' },
    ],
};
