import { Head, Link, router } from '@inertiajs/react';
import { CircleDollarSign } from 'lucide-react';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { Button } from '@/components/ui/button';
import { Metric, Notice, PageTitle, Panel, Pill } from '@/components/ui-brand';
import { formatDateTime } from '@/lib/format';
import type { Paginated } from '@/types/ads';

type Activity = {
    id: string;
    survey: string | null;
    provider: string;
    started_at: string | null;
    completed_at: string | null;
    status: string;
    points: number;
    reward_status: string;
    purpose: string | null;
    used_for: { slug: string; text: string } | null;
    unspent: boolean;
};

type Props = {
    points: { balance: number; lifetime_earned: number };
    activities: Paginated<Activity>;
};

const statusLabel: Record<string, string> = {
    loaded: 'Not started',
    started: 'In progress',
    returned: 'Waiting for confirmation',
    approved: 'Completed',
    rejected: 'Not completed',
    reversed: 'Reversed',
    failed: 'Failed',
    expired: 'Expired',
};

const rewardLabel: Record<string, string> = {
    pending: 'Pending',
    credited: 'Credited',
    reversed: 'Reversed',
    not_eligible: 'No points',
};

const providerLabel: Record<string, string> = {
    tapresearch: 'TapResearch',
    wannads: 'Wannads',
    theoremreach: 'TheoremReach',
};

export default function Earnings({
    points,
    activities,
    filters = { provider: null },
    provider_options = [
        { value: '', label: 'All' },
        { value: 'tapresearch', label: 'TapResearch' },
        { value: 'wannads', label: 'Wannads' },
        { value: 'theoremreach', label: 'TheoremReach' },
    ],
}: Props & {
    filters?: { provider: string | null };
    provider_options?: { value: string; label: string }[];
}) {
    const go = (url: string | null) => {
        if (url) {
            router.get(url, {}, { preserveScroll: true, preserveState: true });
        }
    };

    const setProvider = (provider: string) => {
        router.get(
            '/earnings',
            provider ? { provider } : {},
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <>
            <Head title="My Earnings" />
            <PageTitle icon={CircleDollarSign} title="My Earnings" />

            <Stagger className="grid grid-cols-2 gap-3 px-3 min-[761px]:px-0">
                <StaggerItem>
                    <Metric
                        label="Points balance"
                        value={points.balance.toLocaleString()}
                    />
                </StaggerItem>
                <StaggerItem>
                    <Metric
                        label="Total points earned"
                        value={points.lifetime_earned.toLocaleString()}
                    />
                </StaggerItem>
            </Stagger>

            <div className="px-3 pt-3 min-[761px]:px-0">
                <Notice>
                    Points are added only after the selected provider confirms a
                    completed activity. This is your own history; it shows no
                    estimate of money. AdSense is not an earning source.
                </Notice>
            </div>

            <Reveal>
                <Panel className="mt-3 p-4">
                    <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h2 className="font-bold">My earning history</h2>
                        <Link
                            href="/surveys"
                            className="text-sm font-extrabold text-brand-deep hover:underline"
                        >
                            Earn Rewards →
                        </Link>
                    </div>

                    <div
                        className="mb-3 flex flex-wrap gap-2"
                        role="group"
                        aria-label="Filter by provider"
                    >
                        {provider_options.map((option) => {
                            const active =
                                (filters.provider ?? '') === option.value;
                            return (
                                <Button
                                    key={option.value || 'all'}
                                    size="sm"
                                    variant={active ? 'default' : 'outline'}
                                    onClick={() => setProvider(option.value)}
                                >
                                    {option.label}
                                </Button>
                            );
                        })}
                    </div>

                    {activities.data.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            You have not started a survey yet.
                        </p>
                    ) : (
                        <>
                            <ul className="grid gap-3">
                                {activities.data.map((activity) => (
                                    <li
                                        key={activity.id}
                                        className="grid gap-1 rounded-xl border p-3 text-sm"
                                    >
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <b>
                                                {providerLabel[
                                                    activity.provider
                                                ] ?? activity.provider}{' '}
                                                · {activity.survey ?? '—'}
                                            </b>
                                            <div className="flex items-center gap-2">
                                                <Pill>
                                                    {statusLabel[
                                                        activity.status
                                                    ] ?? activity.status}
                                                </Pill>
                                                <Pill>
                                                    {rewardLabel[
                                                        activity.reward_status
                                                    ] ?? activity.reward_status}
                                                </Pill>
                                            </div>
                                        </div>
                                        <div className="grid gap-0.5 text-xs text-muted-foreground sm:grid-cols-3">
                                            <span>
                                                Started:{' '}
                                                {formatDateTime(
                                                    activity.started_at,
                                                )}
                                            </span>
                                            <span>
                                                Completed:{' '}
                                                {formatDateTime(
                                                    activity.completed_at,
                                                )}
                                            </span>
                                            <span className="font-bold text-foreground">
                                                {activity.points > 0
                                                    ? `+${activity.points} points`
                                                    : '0 points'}
                                            </span>
                                        </div>
                                        {activity.used_for ? (
                                            <p className="text-xs text-muted-foreground">
                                                Unlocked a quote:{' '}
                                                <Link
                                                    href="/studio"
                                                    className="font-bold text-brand-deep hover:underline"
                                                >
                                                    “{activity.used_for.text}”
                                                </Link>
                                            </p>
                                        ) : activity.unspent ? (
                                            <p className="text-xs text-muted-foreground">
                                                {activity.purpose} unlocked —
                                                not used yet.{' '}
                                                <Link
                                                    href="/studio"
                                                    className="font-bold text-brand-deep hover:underline"
                                                >
                                                    Create a quote →
                                                </Link>
                                            </p>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>

                            <div className="mt-3 flex items-center justify-between text-sm text-muted-foreground">
                                <span>
                                    {activities.from}–{activities.to} of{' '}
                                    {activities.total}
                                </span>
                                <div className="flex gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={!activities.prev_page_url}
                                        onClick={() =>
                                            go(activities.prev_page_url)
                                        }
                                    >
                                        Previous
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={!activities.next_page_url}
                                        onClick={() =>
                                            go(activities.next_page_url)
                                        }
                                    >
                                        Next
                                    </Button>
                                </div>
                            </div>
                        </>
                    )}

                    <Link
                        href="/wallet"
                        className="mt-4 inline-block rounded-[10px] border border-brand-soft px-4 py-2.5 text-sm font-extrabold text-brand-deep hover:bg-brand-soft"
                    >
                        Open wallet →
                    </Link>
                </Panel>
            </Reveal>
        </>
    );
}
