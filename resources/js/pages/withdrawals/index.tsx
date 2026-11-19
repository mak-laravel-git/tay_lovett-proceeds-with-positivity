import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeftRight } from 'lucide-react';
import { Reveal } from '@/components/motion/reveal';
import { PageTitle, Panel } from '@/components/ui-brand';
import { Button } from '@/components/ui/button';

type Withdrawal = {
    id: number;
    amount_cents: number;
    status: string;
    destination_label: string;
};

export default function Withdrawals({ requests }: { requests: Withdrawal[] }) {
    return (
        <>
            <Head title="Withdrawals" />
            <PageTitle icon={ArrowLeftRight} title="Withdrawals" />
            <Reveal>
                <Panel className="p-4">
                    <p className="text-sm text-muted-foreground">
                        Requests are reviewed manually. This screen does not
                        send money.
                    </p>
                    <Form
                        action="/withdrawals"
                        method="post"
                        className="mt-5 grid max-w-md gap-3"
                    >
                        {({ errors, processing }) => (
                            <>
                                <label
                                    className="text-xs font-extrabold text-muted-foreground"
                                    htmlFor="amount_cents"
                                >
                                    Amount in cents
                                </label>
                                <input
                                    id="amount_cents"
                                    name="amount_cents"
                                    type="number"
                                    min="1"
                                    className="rounded-[10px] border border-input bg-white p-3 text-ink"
                                />
                                <label
                                    className="text-xs font-extrabold text-muted-foreground"
                                    htmlFor="destination_label"
                                >
                                    Destination label
                                </label>
                                <input
                                    id="destination_label"
                                    name="destination_label"
                                    className="rounded-[10px] border border-input bg-white p-3 text-ink"
                                />
                                {errors.amount && (
                                    <p className="text-sm text-brand-deep">
                                        {errors.amount}
                                    </p>
                                )}
                                <Button
                                    className="h-11 rounded-[10px] font-black"
                                    disabled={processing}
                                    type="submit"
                                >
                                    Request withdrawal
                                </Button>
                            </>
                        )}
                    </Form>
                </Panel>
            </Reveal>
            <Reveal>
                <Panel className="p-4">
                    <h2 className="font-bold">Your transfer history</h2>
                    {requests.length === 0 && (
                        <p className="mt-2 text-sm text-muted-foreground">
                            No transactions are available. Transfer methods,
                            minimum amount, approval process and automatic
                            scheduling are pending confirmation and provider
                            eligibility.
                        </p>
                    )}
                    <ul className="mt-3 grid gap-2">
                        {requests.map((request) => (
                            <li
                                key={request.id}
                                className="rounded-xl border bg-sky-pale/40 p-3 text-sm"
                            >
                                {request.destination_label} ·{' '}
                                {request.amount_cents} cents · {request.status}
                            </li>
                        ))}
                    </ul>
                    <Link
                        href="/wallet"
                        className="mt-3 inline-block rounded-[10px] border border-brand-soft px-4 py-2.5 text-sm font-extrabold text-brand-deep hover:bg-brand-soft"
                    >
                        Back to wallet
                    </Link>
                </Panel>
            </Reveal>
        </>
    );
}
