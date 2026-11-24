import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import {
    fetchActivityStatus,
    fetchOffers,
    simulateCompletion,
    startOffer,
} from '@/lib/ads-api';
import type {
    ActivityStatusResponse,
    GatePurpose,
    Offer,
    OffersResponse,
} from '@/lib/ads-api';

// Surveys are re-listed about every two minutes: they fill up and most do not allow a second entry.
const REFRESH_MS = 120_000;
const POLL_MS = 3_000;
const POLL_LIMIT = 40;

export type SurveyFlowOptions = {
    /** What the survey is being started for; stored on the transaction so it can unlock that action. */
    purpose?: GatePurpose;
    /** Load and keep refreshing only while true (a dialog passes its open state here). */
    enabled?: boolean;
    /** When set, ask only this provider instead of running the fallback chain. */
    providerKey?: string;
    /** Called once the provider has confirmed a completed survey. */
    onApproved?: (status: ActivityStatusResponse) => void;
};

type Waiting = { activityId: string; surveyId: string };

export type OpenSurvey = { url: string; surveyId: string; activityId: string };

export type SurveyFlow = ReturnType<typeof useSurveyFlow>;

/**
 * Everything one ad / survey flow needs: list what the winning provider offers, open an offer in a frame, and
 * wait for the provider's confirmation. The browser never approves anything: it only reads the status that the
 * verified provider callback has already written on the server.
 */
export function useSurveyFlow({
    purpose,
    enabled = true,
    providerKey,
    onApproved,
}: SurveyFlowOptions = {}) {
    const [list, setList] = useState<OffersResponse | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [open, setOpen] = useState<OpenSurvey | null>(null);
    const [waiting, setWaiting] = useState<Waiting | null>(null);
    const [starting, setStarting] = useState<string | null>(null);
    const [simulating, setSimulating] = useState(false);

    const onApprovedRef = useRef(onApproved);

    useEffect(() => {
        onApprovedRef.current = onApproved;
    }, [onApproved]);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            setList(await fetchOffers(providerKey));
        } catch (caught) {
            setError(
                caught instanceof Error
                    ? caught.message
                    : 'Could not load surveys.',
            );
        } finally {
            setLoading(false);
        }
    }, [providerKey]);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        void load();

        const timer = window.setInterval(() => void load(), REFRESH_MS);

        return () => window.clearInterval(timer);
    }, [enabled, load]);

    // The return page tells us the user has come back; it never says the survey succeeded.
    useEffect(() => {
        if (!enabled) {
            return;
        }

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
    }, [enabled, load]);

    const start = useCallback(
        async (offer: Offer) => {
            if (!list) {
                return;
            }

            setStarting(offer.identifier);

            try {
                const response = await startOffer({
                    request_id: list.request_id,
                    offer_identifier: offer.identifier,
                    purpose,
                });

                setWaiting({
                    activityId: response.activity_id,
                    surveyId: offer.identifier,
                });
                setOpen({
                    url: response.url,
                    surveyId: offer.identifier,
                    activityId: response.activity_id,
                });
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
        },
        [list, load, purpose],
    );

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
                const status = await fetchActivityStatus(waiting.activityId);

                if (stopped) {
                    return;
                }

                if (status.status === 'approved') {
                    stopped = true;
                    setWaiting(null);
                    setOpen(null);
                    toast.success(
                        status.points > 0
                            ? `Confirmed! You earned ${status.points} points.`
                            : 'Your survey was confirmed.',
                    );
                    onApprovedRef.current?.(status);

                    return;
                }

                if (status.status === 'rejected') {
                    stopped = true;
                    setWaiting(null);
                    setOpen(null);
                    toast.info(
                        'This survey was not completed, so no points were awarded.',
                    );
                    void load();

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
    }, [waiting, load]);

    const closeSurvey = useCallback(() => {
        setOpen(null);
        void load();
    }, [load]);

    const openInWindow = useCallback(() => {
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
    }, [open]);

    // Local development only: the provider cannot reach this machine, so complete the survey ourselves.
    const simulate = useCallback(async () => {
        if (!open) {
            return;
        }

        setSimulating(true);

        try {
            await simulateCompletion(open.activityId);
        } catch (caught) {
            toast.error(
                caught instanceof Error
                    ? caught.message
                    : 'Could not simulate completion.',
            );
        } finally {
            setSimulating(false);
        }
    }, [open]);

    return {
        list,
        loading,
        error,
        open,
        waiting,
        starting,
        simulating,
        canSimulate: list?.simulate === true,
        load,
        start,
        closeSurvey,
        openInWindow,
        simulate,
    };
}
