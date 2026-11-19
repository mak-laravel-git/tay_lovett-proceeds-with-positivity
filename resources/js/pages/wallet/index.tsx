import { Head, Link } from '@inertiajs/react';
import { Wallet } from 'lucide-react';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { Metric, PageTitle, Panel } from '@/components/ui-brand';

type WalletProps = {
    wallet: {
        pending_cents: number;
        available_cents: number;
        reserved_cents: number;
    };
    entries: Array<{
        entry_type: string;
        amount_cents: number;
        description: string;
    }>;
};

function money(cents: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(cents / 100);
}

export default function WalletPage({ wallet, entries }: WalletProps) {
    return (
        <>
            <Head title="Wallet" />
            <PageTitle icon={Wallet} title="My Wallet" />
            <Stagger className="grid grid-cols-2 gap-3 px-3 min-[761px]:grid-cols-3 min-[761px]:px-0">
                <StaggerItem>
                    <Metric
                        label="Available balance"
                        value={money(wallet.available_cents)}
                        note="Verified balance"
                    />
                </StaggerItem>
                <StaggerItem>
                    <Metric
                        label="Pending earnings"
                        value={money(wallet.pending_cents)}
                        note={
                            wallet.pending_cents > 0
                                ? 'Sandbox / unsettled — not withdrawable'
                                : 'No verified pending share yet'
                        }
                    />
                </StaggerItem>
                <StaggerItem className="col-span-2 min-[761px]:col-span-1">
                    <Metric
                        label="Revenue share"
                        value="⅓"
                        note="Sandbox rule when admin enables Option B"
                    />
                </StaggerItem>
            </Stagger>
            <Reveal className="mt-4">
                <Panel className="p-4">
                    <h2 className="font-bold">Account activity</h2>
                    {entries.length === 0 && (
                        <p className="mt-2 text-sm text-muted-foreground">
                            Once a compliant advertising provider and approved
                            tracking method are connected, eligible earnings and
                            transactions can appear here.
                        </p>
                    )}
                    <ul className="mt-3 grid gap-2">
                        {entries.map((entry, index) => (
                            <li
                                key={index}
                                className="rounded-xl border bg-sky-pale/40 p-3 text-sm"
                            >
                                {entry.description} ·{' '}
                                {money(entry.amount_cents)}
                            </li>
                        ))}
                    </ul>
                    <Link
                        href="/withdrawals"
                        className="mt-3 inline-block rounded-[10px] border border-brand-soft px-4 py-2.5 text-sm font-extrabold text-brand-deep hover:bg-brand-soft"
                    >
                        View withdrawals →
                    </Link>
                </Panel>
            </Reveal>
        </>
    );
}
