import { Head, router } from '@inertiajs/react';
import {
    ClipboardList,
    Clock,
    ExternalLink,
    Flame,
    LifeBuoy,
    RefreshCw,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { Button } from '@/components/ui/button';
import { Metric, Notice, PageTitle, Panel, Pill } from '@/components/ui-brand';
import { xsrfToken } from '@/lib/csrf';

type Offer = {
    identifier: string;
    length_minutes: number | null;
    category: string | null;
    support_url: string | null;
    is_hot: boolean;
};

type OffersResponse = {
    request_id: number;
    provider: string | null;
    offers: Offer[];
};

type Waiting = { activityId: string; surveyId: string };

type Status = {
    status: string;
    reward_status: string;
    points: number;
};

// Surveys are re-listed about every two minutes: they fill up and most do not allow a second entry.
const REFRESH_MS = 120_000;
const POLL_MS = 3_000;
const POLL_LIMIT = 40;

async function postJson<T>(url: string, body?: unknown): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    if (!response.ok) {
        const data = (await response.json().catch(() => ({}))) as {
            message?: string;
        };

        throw new Error(
            data.message ?? 'Something went wrong. Please try again.',
        );
    }

    return (await response.json()) as T;
}

export default function Surveys({
    points,
}: {
    points: { balance: number; lifetime_earned: number };
}) {
    const [list, setList] = useState<OffersResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [open, setOpen] = useState<{ url: string; surveyId: string } | null>(
        null,
    );
    const [waiting, setWaiting] = useState<Waiting | null>(null);
    const [starting, setStarting] = useState<string | null>(null);
    const activityRef = useRef<string | null>(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            setList(await postJson<OffersResponse>('/surveys/offers'));
        } catch (caught) {
            setError(
                caught instanceof Error
                    ? caught.message
                    : 'Could not load surveys.',
            );
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        void load();

        const timer = window.setInterval(() => void load(), REFRESH_MS);

        return () => window.clearInterval(timer);
    }, [load]);

    // The return page tells us the user has come back; it never says the survey succeeded.
    useEffect(() => {
        const onMessage = (event: MessageEvent) => {
            const data = event.data as { type?: string } | null;

            if (
                event.origin === window.location.origin &&
                data?.type === 'survey-returned'
            ) {
                setOpen(null);
                void load();
            }
        };

        window.addEventListener('message', onMessage);

        return () => window.removeEventListener('message', onMessage);
    }, [load]);

    const start = async (offer: Offer) => {
        if (!list) {
            return;
        }

        setStarting(offer.identifier);

        try {
            const response = await postJson<{
                activity_id: string;
                url: string;
            }>('/surveys/start', {
                request_id: list.request_id,
                offer_identifier: offer.identifier,
            });

            activityRef.current = response.activity_id;
            setWaiting({
                activityId: response.activity_id,
                surveyId: offer.identifier,
            });
            setOpen({ url: response.url, surveyId: offer.identifier });
            // It cannot be started twice, so take it off the list straight away.
            setList({
                ...list,
                offers: list.offers.filter(
                    (item) => item.identifier !== offer.identifier,
                ),
            });
        } catch (caught) {
            toast.error(
                caught instanceof Error
                    ? caught.message
                    : 'Could not start this survey.',
            );
            void load();
        } finally {
            setStarting(null);
        }
    };

    // Once a survey has been opened, wait for the provider's confirmation (read-only polling).
    useEffect(() => {
        if (!waiting) {
            return;
        }

        let attempts = 0;
        let stopped = false;

        const check = async () => {
            attempts += 1;

            try {
                const response = await fetch(
                    `/surveys/activities/${waiting.activityId}/status`,
                    {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    },
                );
                const status = (await response.json()) as Status;

                if (stopped) {
                    return;
                }

                if (status.status === 'approved') {
                    stopped = true;
                    setWaiting(null);
                    toast.success(
                        status.points > 0
                            ? `Confirmed! You earned ${status.points} points.`
                            : 'Your survey was confirmed.',
                    );
                    router.reload({ only: ['points'] });

                    return;
                }

                if (status.status === 'rejected') {
                    stopped = true;
                    setWaiting(null);
                    toast.info(
                        'This survey was not completed, so no points were awarded.',
                    );

                    return;
                }
            } catch {
                // Try again on the next tick.
            }

            if (attempts >= POLL_LIMIT) {
                stopped = true;
                setWaiting(null);
                toast.info(
                    'Confirmation can take a few minutes. Check My Earnings later.',
                );
            }
        };

        const timer = window.setInterval(() => void check(), POLL_MS);

        return () => {
            stopped = true;
            window.clearInterval(timer);
        };
    }, [waiting]);

    const closeSurvey = () => {
        setOpen(null);
        void load();
    };

    const openInWindow = () => {
        if (!open) {
            return;
        }

        const popup = window.open(
            open.url,
            'survey',
            'popup=yes,width=1000,height=800,noopener=no',
        );

        if (popup) {
            setOpen(null);
        } else {
            toast.error(
                'Your browser blocked the pop-up. Please allow pop-ups for this site.',
            );
        }
    };

    return (
        <>
            <Head title="Surveys" />
            <PageTitle
                icon={ClipboardList}
                title="Surveys"
                pill="Earn points"
            />

            <Stagger className="grid grid-cols-2 gap-3 px-3 min-[761px]:px-0">
                <StaggerItem>
                    <Metric
                        label="My points"
                        value={points.balance.toLocaleString()}
                    />
                </StaggerItem>
                <StaggerItem>
                    <Metric
                        label="Total earned"
                        value={points.lifetime_earned.toLocaleString()}
                    />
                </StaggerItem>
            </Stagger>

            <div className="px-3 pt-3 min-[761px]:px-0">
                <Notice>
                    Points are added only after the survey provider confirms
                    that you completed a survey — that can take a few minutes.
                    If you do not qualify for a survey, it earns no points.
                </Notice>
            </div>

            {waiting && !open ? (
                <div className="px-3 pt-3 min-[761px]:px-0" role="status">
                    <Notice>
                        Checking with the provider for survey {waiting.surveyId}
                        …
                    </Notice>
                </div>
            ) : null}

            <Reveal>
                <Panel className="mt-3 p-4">
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <h2 className="font-bold">Available surveys</h2>
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={loading}
                            onClick={() => void load()}
                        >
                            <RefreshCw
                                className={loading ? 'animate-spin' : ''}
                            />
                            Refresh
                        </Button>
                    </div>

                    {error ? (
                        <p className="text-sm text-destructive" role="alert">
                            {error}
                        </p>
                    ) : null}

                    {!error && !loading && (list?.offers.length ?? 0) === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No surveys are available right now. Please check
                            back in a few minutes.
                        </p>
                    ) : null}

                    <ul className="grid gap-3">
                        {(list?.offers ?? []).map((offer) => (
                            <li
                                key={offer.identifier}
                                className="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3"
                            >
                                <div className="grid gap-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <b>Survey {offer.identifier}</b>
                                        {offer.is_hot ? (
                                            <Pill className="inline-flex items-center gap-1">
                                                <Flame className="size-3" />{' '}
                                                Popular
                                            </Pill>
                                        ) : null}
                                        {offer.category ? (
                                            <Pill>{offer.category}</Pill>
                                        ) : null}
                                    </div>
                                    <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                        <Clock className="size-3.5" />
                                        {offer.length_minutes
                                            ? `About ${offer.length_minutes} min`
                                            : 'Length not given'}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    {offer.support_url ? (
                                        <a
                                            href={offer.support_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1 text-xs font-bold text-brand-deep hover:underline"
                                        >
                                            <LifeBuoy className="size-3.5" />{' '}
                                            Get help
                                        </a>
                                    ) : null}
                                    <Button
                                        size="sm"
                                        disabled={
                                            starting !== null || open !== null
                                        }
                                        onClick={() => void start(offer)}
                                    >
                                        {starting === offer.identifier
                                            ? 'Opening…'
                                            : 'Start'}
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                </Panel>
            </Reveal>

            {open ? (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label={`Survey ${open.surveyId}`}
                    className="fixed inset-0 z-50 flex flex-col bg-ink"
                >
                    <div className="flex items-center justify-between gap-2 border-b-[3px] border-brand bg-ink px-3 py-2 text-white">
                        <span className="text-sm font-bold">
                            Survey {open.surveyId}
                        </span>
                        <div className="flex items-center gap-2">
                            <Button
                                size="sm"
                                variant="secondary"
                                onClick={openInWindow}
                                title="Use this if the survey does not appear below"
                            >
                                <ExternalLink /> Not loading? Open in a window
                            </Button>
                            <Button
                                size="sm"
                                variant="secondary"
                                onClick={closeSurvey}
                            >
                                <X /> Close
                            </Button>
                        </div>
                    </div>
                    <iframe
                        title={`Survey ${open.surveyId}`}
                        src={open.url}
                        className="min-h-0 flex-1 border-0 bg-white"
                        referrerPolicy="strict-origin-when-cross-origin"
                        sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation"
                    />
                </div>
            ) : null}
        </>
    );
}
