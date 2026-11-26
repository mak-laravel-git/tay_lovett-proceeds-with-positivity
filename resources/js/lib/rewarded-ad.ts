import { enableGptServices, loadGpt } from '@/lib/google-ads';
import type { GoogleTag, GptEvent, GptSlot } from '@/lib/google-ads';

export type RewardedAdHandlers = {
    /** The ad can be shown. Call `show` only after the user has said yes; it returns false if it could not. */
    onReady: (show: () => boolean) => void;
    onGranted: () => void;
    /** The user closed the ad, with or without having earned the reward. */
    onClosed: () => void;
    /** No ad for this request, or the device / page does not support rewarded ads. */
    onUnavailable: (detail: string) => void;
    /** The ad could not be loaded: blocked script, timeout or an unexpected failure. */
    onError: (detail: string) => void;
};

// Google allows one rewarded slot at a time, so starting an ad ends the previous one.
let dispose: (() => void) | null = null;

/**
 * Request one Google rewarded web ad (Google Publisher Tag, Ad Manager). Returns a function that abandons it.
 * The slot is always destroyed again, whatever the outcome.
 */
export function runRewardedAd(
    adUnitPath: string,
    timeoutMs: number,
    handlers: RewardedAdHandlers,
): () => void {
    dispose?.();

    let finished = false;
    let timer: number | undefined;
    let teardown: (() => void) | null = null;

    const stop = () => {
        finished = true;
        window.clearTimeout(timer);
        teardown?.();
        teardown = null;

        if (dispose === stop) {
            dispose = null;
        }
    };

    const end = (report: () => void) => {
        if (!finished) {
            stop();
            report();
        }
    };

    dispose = stop;
    timer = window.setTimeout(
        () => end(() => handlers.onError('timeout')),
        timeoutMs,
    );

    const request = (googletag: GoogleTag) => {
        if (finished) {
            return;
        }

        const slot: GptSlot | null = googletag.defineOutOfPageSlot(
            adUnitPath,
            googletag.enums.OutOfPageFormat.REWARDED,
        );

        if (!slot) {
            end(() => handlers.onUnavailable('unsupported'));

            return;
        }

        const pubads = googletag.pubads();
        const mine =
            (listener: (event: GptEvent) => void) => (event: GptEvent) => {
                if (!finished && event.slot === slot) {
                    listener(event);
                }
            };

        const ready = mine((event) => {
            window.clearTimeout(timer);
            handlers.onReady(() => event.makeRewardedVisible?.() === true);
        });
        const granted = mine(() => handlers.onGranted());
        const closed = mine(() => end(() => handlers.onClosed()));
        const rendered = mine((event) => {
            if (event.isEmpty) {
                end(() => handlers.onUnavailable('no_fill'));
            }
        });

        pubads.addEventListener('rewardedSlotReady', ready);
        pubads.addEventListener('rewardedSlotGranted', granted);
        pubads.addEventListener('rewardedSlotClosed', closed);
        pubads.addEventListener('slotRenderEnded', rendered);

        teardown = () => {
            pubads.removeEventListener('rewardedSlotReady', ready);
            pubads.removeEventListener('rewardedSlotGranted', granted);
            pubads.removeEventListener('rewardedSlotClosed', closed);
            pubads.removeEventListener('slotRenderEnded', rendered);
            googletag.destroySlots([slot]);
        };

        slot.addService(pubads);
        enableGptServices(googletag);
        googletag.display(slot);
    };

    loadGpt()
        .then((googletag) => {
            try {
                request(googletag);
            } catch {
                end(() => handlers.onError('request_failed'));
            }
        })
        .catch(() => end(() => handlers.onError('script_blocked')));

    return stop;
}
