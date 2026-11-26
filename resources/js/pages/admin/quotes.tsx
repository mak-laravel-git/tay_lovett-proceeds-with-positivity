import { Form, Head } from '@inertiajs/react';
import { Check, EyeOff, Plus, Send, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    AdminEmpty,
    AdminPage,
    AdminSectionCard,
    StatusBadge,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type ModerationQuote = {
    slug: string;
    text: string;
    category: string;
    status: string;
    rejection_reason: string | null;
};

type Category = { id: number; name: string };

const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'pending_review', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'published', label: 'Published' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'draft', label: 'Draft' },
    { value: 'awaiting_unlock', label: 'Awaiting unlock' },
] as const;

export default function AdminQuotes({
    quotes,
    categories,
}: {
    quotes: ModerationQuote[];
    categories: Category[];
}) {
    const [status, setStatus] =
        useState<(typeof statusTabs)[number]['value']>('all');
    const [createOpen, setCreateOpen] = useState(false);
    const [rejectTarget, setRejectTarget] = useState<ModerationQuote | null>(
        null,
    );
    const [categoryId, setCategoryId] = useState(
        categories[0] ? String(categories[0].id) : '',
    );

    const filtered = useMemo(
        () =>
            status === 'all'
                ? quotes
                : quotes.filter((quote) => quote.status === status),
        [quotes, status],
    );

    return (
        <>
            <Head title="Moderation" />
            <AdminPage
                title="Quote moderation"
                description="Review, approve, publish, or reject quotes before they reach the feed."
                actions={
                    <Button
                        type="button"
                        className="bg-[#ed2948] text-white hover:bg-[#d61f3d]"
                        onClick={() => setCreateOpen(true)}
                        disabled={categories.length === 0}
                    >
                        <Plus className="size-4" />
                        Add quote
                    </Button>
                }
            >
                <AdminSectionCard
                    title="Queue"
                    description={`${filtered.length} quote${filtered.length === 1 ? '' : 's'}`}
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
                        <AdminEmpty>No quotes in this filter.</AdminEmpty>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[720px] text-left text-sm">
                                <thead className="border-b text-muted-foreground">
                                    <tr>
                                        <th className="px-2 py-2.5 font-medium">
                                            Quote
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Status
                                        </th>
                                        <th className="px-2 py-2.5 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filtered.map((quote) => (
                                        <tr
                                            key={quote.slug}
                                            className="border-b border-[#eef5f8] last:border-0"
                                        >
                                            <td className="px-2 py-3 align-top">
                                                <p className="max-w-xl text-[#0a1018]">
                                                    {quote.text}
                                                </p>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {quote.category}
                                                </p>
                                                {quote.rejection_reason ? (
                                                    <p className="mt-1 text-xs text-[#ac1832]">
                                                        {quote.rejection_reason}
                                                    </p>
                                                ) : null}
                                            </td>
                                            <td className="px-2 py-3 align-top">
                                                <StatusBadge
                                                    value={quote.status}
                                                />
                                            </td>
                                            <td className="px-2 py-3 align-top">
                                                <div className="flex flex-wrap justify-end gap-1.5">
                                                    {quote.status ===
                                                        'pending_review' ||
                                                    quote.status === 'draft' ||
                                                    // Releases a quote that is still waiting for its last workflow step.
                                                    quote.status ===
                                                        'awaiting_unlock' ? (
                                                        <Form
                                                            action={`/admin/quotes/${quote.slug}/approve`}
                                                            method="post"
                                                        >
                                                            <Button
                                                                type="submit"
                                                                size="sm"
                                                                variant="outline"
                                                                className="border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                                                            >
                                                                <Check className="size-3.5" />
                                                                Approve
                                                            </Button>
                                                        </Form>
                                                    ) : null}
                                                    {quote.status ===
                                                        'approved' ||
                                                    quote.status ===
                                                        'pending_review' ? (
                                                        <Form
                                                            action={`/admin/quotes/${quote.slug}/publish`}
                                                            method="post"
                                                        >
                                                            <Button
                                                                type="submit"
                                                                size="sm"
                                                                variant="outline"
                                                                className="border-[#76d8f8]/50 bg-[#e7f7ff] text-[#0f6f8f] hover:bg-[#d8f3fb]"
                                                            >
                                                                <Send className="size-3.5" />
                                                                Publish
                                                            </Button>
                                                        </Form>
                                                    ) : null}
                                                    {quote.status ===
                                                    'published' ? (
                                                        <Form
                                                            action={`/admin/quotes/${quote.slug}/unpublish`}
                                                            method="post"
                                                        >
                                                            <Button
                                                                type="submit"
                                                                size="sm"
                                                                variant="outline"
                                                            >
                                                                <EyeOff className="size-3.5" />
                                                                Unpublish
                                                            </Button>
                                                        </Form>
                                                    ) : null}
                                                    {quote.status !==
                                                        'rejected' &&
                                                    quote.status !==
                                                        'published' ? (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            className="border-[#ed2948]/30 text-[#ed2948] hover:bg-[#ffedf0]"
                                                            onClick={() =>
                                                                setRejectTarget(
                                                                    quote,
                                                                )
                                                            }
                                                        >
                                                            <X className="size-3.5" />
                                                            Reject
                                                        </Button>
                                                    ) : null}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </AdminSectionCard>
            </AdminPage>

            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Add quote for review</DialogTitle>
                        <DialogDescription>
                            New quotes enter pending review. Status cannot be
                            chosen here.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        action="/admin/quotes"
                        method="post"
                        className="grid gap-3"
                        onSuccess={() => setCreateOpen(false)}
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label>Category</Label>
                                    <input
                                        type="hidden"
                                        name="quote_category_id"
                                        value={categoryId}
                                    />
                                    <Select
                                        value={categoryId}
                                        onValueChange={setCategoryId}
                                    >
                                        <SelectTrigger className="w-full bg-white">
                                            <SelectValue placeholder="Category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((category) => (
                                                <SelectItem
                                                    key={category.id}
                                                    value={String(category.id)}
                                                >
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.quote_category_id ? (
                                        <p className="text-sm text-[#ac1832]">
                                            {errors.quote_category_id}
                                        </p>
                                    ) : null}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="quote-text">Quote</Label>
                                    <Textarea
                                        id="quote-text"
                                        name="text"
                                        rows={4}
                                        className="bg-white"
                                    />
                                    {errors.text ? (
                                        <p className="text-sm text-[#ac1832]">
                                            {errors.text}
                                        </p>
                                    ) : null}
                                </div>
                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setCreateOpen(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-[#ed2948] text-white hover:bg-[#d61f3d]"
                                    >
                                        Submit for review
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={rejectTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRejectTarget(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Reject quote</DialogTitle>
                        <DialogDescription>
                            A reason is required and stays visible on the
                            moderation queue.
                        </DialogDescription>
                    </DialogHeader>
                    {rejectTarget ? (
                        <Form
                            action={`/admin/quotes/${rejectTarget.slug}/reject`}
                            method="post"
                            className="grid gap-3"
                            onSuccess={() => setRejectTarget(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="reject-reason">
                                            Reason
                                        </Label>
                                        <Input
                                            id="reject-reason"
                                            name="reason"
                                            className="bg-white"
                                        />
                                        {errors.reason ? (
                                            <p className="text-sm text-[#ac1832]">
                                                {errors.reason}
                                            </p>
                                        ) : null}
                                    </div>
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                setRejectTarget(null)
                                            }
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="bg-[#ed2948] text-white hover:bg-[#d61f3d]"
                                        >
                                            Reject
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

AdminQuotes.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Moderation', href: '/admin/quotes' },
    ],
};
