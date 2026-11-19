import { Link } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import { AccountSection } from '@/components/account/account-section';
import { formatDateTime } from '@/lib/format';

export type AdStats = {
    surveys_started: number;
    surveys_completed: number;
    surveys_not_completed: number;
    points_balance: number;
    points_lifetime: number;
    quotes_unlocked: number;
    unlocks_available: number;
    last_completed_at: string | null;
    by_provider: { provider: string; label: string; completed: number }[];
};

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-line bg-sky-wash px-3.5 py-3">
            <div className="text-xs font-bold text-muted-ink">{label}</div>
            <div className="mt-0.5 text-xl font-extrabold text-ink">
                {value}
            </div>
        </div>
    );
}

/** A user's own survey activity summary, shown on their profile. The full history is on My Earnings. */
export function AdActivitySection({ stats }: { stats: AdStats }) {
    return (
        <AccountSection
            title="Survey activity"
            description="What you have completed to unlock quotes. Confirmed by the survey provider."
            icon={<ClipboardList className="size-5" />}
            actions={
                <Link
                    href="/earnings"
                    className="text-sm font-extrabold text-brand-deep hover:underline"
                >
                    Full history →
                </Link>
            }
        >
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <Stat
                    label="Surveys completed"
                    value={stats.surveys_completed.toLocaleString()}
                />
                <Stat
                    label="Quotes unlocked"
                    value={stats.quotes_unlocked.toLocaleString()}
                />
                <Stat
                    label="Ready to use"
                    value={stats.unlocks_available.toLocaleString()}
                />
                <Stat
                    label="Points balance"
                    value={stats.points_balance.toLocaleString()}
                />
                <Stat
                    label="Total points earned"
                    value={stats.points_lifetime.toLocaleString()}
                />
                <Stat
                    label="Not completed"
                    value={stats.surveys_not_completed.toLocaleString()}
                />
            </div>
            <p className="mt-3 text-xs text-muted-ink">
                Last completed:{' '}
                {stats.last_completed_at
                    ? formatDateTime(stats.last_completed_at)
                    : 'none yet'}
                {stats.by_provider.length > 0
                    ? ` · ${stats.by_provider
                          .map((row) => `${row.label}: ${row.completed}`)
                          .join(', ')}`
                    : ''}
            </p>
        </AccountSection>
    );
}
