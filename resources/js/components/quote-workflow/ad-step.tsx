import { Play, RotateCw } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { runRewardedAd } from '@/lib/rewarded-ad';
import { issueAd, reportAdEvent } from '@/lib/workflow-api';
import type { AdEventName, AdUnit, Workflow } from '@/lib/workflow-api';

type Phase = 'loading' | 'ready' | 'playing' | 'stopped';

type Props = {
    workflow: Workflow;
    onChange: (workflow: Workflow) => void;
};

/**
 * One rewarded ad step: load the ad, ask the user, show it, and tell the server what happened. The server
 * decides whether the run moves on. The new state is only applied once the ad is off the screen.
 */
export function AdStep({ workflow, onChange }: Props) {
    const [phase, setPhase] = useState<Phase>('loading');
    const [message, setMessage] = useState<string | null>(null);
    const show = useRef<(() => boolean) | null>(null);
    const stop = useRef<(() => void) | null>(null);
    // Events must reach the server in the order they happened.
    const queue = useRef<Promise<unknown>>(Promise.resolve());
    const latest = useRef<Workflow>(workflow);
    const shownAt = useRef(0);
    const unit = useRef<AdUnit | null>(null);
    const mounted = useRef(true);
    const id = workflow.id;

    const fail = useCallback((caught: unknown) => {
        if (mounted.current) {
            setMessage(
                caught instanceof Error
                    ? caught.message
                    : 'Something went wrong. Please try again.',
            );
            setPhase('stopped');
        }
    }, []);

    /** Queue an event; `settles` marks the ones after which the page may move on. */
    const report = useCallback(
        (
            event: AdEventName,
            { detail, settles }: { detail?: string; settles?: boolean } = {},
        ) => {
            const attempt = unit.current;

            if (!attempt) {
                return;
            }

            queue.current = queue.current
                .then(async () => {
                    if (event === 'granted') {
                        // The server refuses a reward reported sooner than the ad could have played.
                        const wait =
                            shownAt.current +
                            attempt.min_watch_seconds * 1000 -
                            Date.now();

                        if (wait > 0) {
                            await new Promise((resolve) =>
                                window.setTimeout(resolve, wait),
                            );
                        }
                    }

                    const response = await reportAdEvent(
                        id,
                        attempt.attempt_id,
                        event,
                        detail,
                    );
                    latest.current = response.workflow;

                    if (event === 'shown') {
                        shownAt.current = Date.now();
                    }

                    if (settles && mounted.current) {
                        setMessage(response.message);
                        setPhase('stopped');
                        onChange(response.workflow);
                    }
                })
                .catch(fail);
        },
        [id, onChange, fail],
    );

    const load = useCallback(async () => {
        setPhase('loading');
        setMessage(null);

        try {
            const response = await issueAd(id);

            if (!mounted.current) {
                return;
            }

            latest.current = response.workflow;
            unit.current = response.ad;

            if (!response.ad) {
                // Either the rules let the user go on without an ad, or they explain why not.
                setMessage(response.message);
                setPhase('stopped');
                onChange(response.workflow);

                return;
            }

            stop.current = runRewardedAd(
                response.ad.ad_unit_path,
                response.ad.load_timeout_seconds * 1000,
                {
                    onReady: (makeVisible) => {
                        show.current = makeVisible;
                        setPhase('ready');
                        report('ready');
                    },
                    onGranted: () => report('granted'),
                    onClosed: () => report('closed', { settles: true }),
                    onUnavailable: (detail) =>
                        report('unavailable', { detail, settles: true }),
                    onError: (detail) =>
                        report('error', { detail, settles: true }),
                },
            );
        } catch (caught) {
            fail(caught);
        }
    }, [id, onChange, report, fail]);

    useEffect(() => {
        mounted.current = true;
        // Deferred so that a mount which is immediately undone (React strict mode) requests nothing.
        const timer = window.setTimeout(() => void load(), 0);

        return () => {
            mounted.current = false;
            window.clearTimeout(timer);
            stop.current?.();
        };
    }, [load]);

    const watch = () => {
        setPhase('playing');
        report('shown');

        // Called straight from the click, which browsers require to start video with sound.
        if (!show.current?.()) {
            stop.current?.();
            report('error', { detail: 'could_not_show', settles: true });
        }
    };

    const decline = () => {
        stop.current?.();
        report('declined', { settles: true });
    };

    return (
        <div className="grid gap-3" aria-live="polite">
            {phase === 'loading' ? (
                <p className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Spinner /> Loading an ad…
                </p>
            ) : null}

            {phase === 'ready' ? (
                <>
                    <p className="text-sm">
                        Watch a short ad to{' '}
                        {workflow.state === 'ad_before_publish'
                            ? 'unlock your quote'
                            : 'continue to the next step'}
                        . You can close it at any time, but the step is only
                        completed when the ad has finished.
                    </p>
                    <div className="flex flex-wrap gap-2">
                        <Button onClick={watch}>
                            <Play /> Watch ad
                        </Button>
                        <Button variant="outline" onClick={decline}>
                            Not now
                        </Button>
                    </div>
                </>
            ) : null}

            {phase === 'playing' ? (
                <p className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Spinner /> Ad in progress…
                </p>
            ) : null}

            {phase === 'stopped' ? (
                <>
                    {message ? (
                        <p className="text-sm text-brand-deep" role="alert">
                            {message}
                        </p>
                    ) : null}
                    <Button
                        className="w-fit"
                        variant="outline"
                        onClick={() => void load()}
                    >
                        <RotateCw /> Try again
                    </Button>
                </>
            ) : null}
        </div>
    );
}
