/**
 * Loading Google's two ad scripts. Each is added to the page once; a failed load (usually an ad blocker) is
 * reported to the caller and can be retried.
 */

export type GptSlot = {
    addService: (service: GptPubAds) => GptSlot;
};

export type GptEvent = {
    slot: GptSlot;
    isEmpty?: boolean;
    makeRewardedVisible?: () => boolean;
};

export type GptEventName =
    | 'rewardedSlotReady'
    | 'rewardedSlotGranted'
    | 'rewardedSlotClosed'
    | 'slotRenderEnded';

export type GptPubAds = {
    addEventListener: (
        name: GptEventName,
        listener: (event: GptEvent) => void,
    ) => void;
    removeEventListener: (
        name: GptEventName,
        listener: (event: GptEvent) => void,
    ) => void;
};

export type GoogleTag = {
    cmd: { push: (command: () => void) => void };
    pubadsReady?: boolean;
    pubads: () => GptPubAds;
    enableServices: () => void;
    defineSlot: (
        path: string,
        sizes: [number, number][] | 'fluid',
        elementId: string,
    ) => GptSlot | null;
    defineOutOfPageSlot: (path: string, format: unknown) => GptSlot | null;
    display: (target: GptSlot | string) => void;
    destroySlots: (slots: GptSlot[]) => boolean;
    enums: { OutOfPageFormat: { REWARDED: unknown } };
};

declare global {
    interface Window {
        googletag?: Partial<GoogleTag> & { cmd: GoogleTag['cmd'] };
        adsbygoogle?: Record<string, unknown>[];
    }
}

const GPT_URL = 'https://securepubads.g.doubleclick.net/tag/js/gpt.js';
const ADSENSE_URL =
    'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js';
const SCRIPT_TIMEOUT_MS = 10_000;

const scripts = new Map<string, Promise<void>>();

function loadScript(
    src: string,
    attributes: Record<string, string> = {},
): Promise<void> {
    const known = scripts.get(src);

    if (known) {
        return known;
    }

    const loading = new Promise<void>((resolve, reject) => {
        const element = document.createElement('script');

        const fail = () => {
            window.clearTimeout(timer);
            element.remove();
            scripts.delete(src);
            reject(new Error('script_blocked'));
        };
        const timer = window.setTimeout(fail, SCRIPT_TIMEOUT_MS);

        element.src = src;
        element.async = true;
        Object.entries(attributes).forEach(([name, value]) =>
            element.setAttribute(name, value),
        );
        element.onload = () => {
            window.clearTimeout(timer);
            resolve();
        };
        element.onerror = fail;
        document.head.appendChild(element);
    });

    scripts.set(src, loading);

    return loading;
}

/** Google Publisher Tag, ready to take commands. */
export async function loadGpt(): Promise<GoogleTag> {
    // GPT picks up commands queued before its script has arrived.
    const queue: (() => void)[] = [];
    window.googletag = window.googletag ?? { cmd: queue };
    await loadScript(GPT_URL);

    return new Promise<GoogleTag>((resolve, reject) => {
        // Some blockers answer with an empty script: it "loads" but never runs the queue.
        const timer = window.setTimeout(
            () => reject(new Error('script_blocked')),
            SCRIPT_TIMEOUT_MS,
        );

        window.googletag?.cmd.push(() => {
            window.clearTimeout(timer);
            resolve(window.googletag as GoogleTag);
        });
    });
}

/** The AdSense script for one publisher account. */
export function loadAdSense(clientId: string): Promise<void> {
    return loadScript(`${ADSENSE_URL}?client=${encodeURIComponent(clientId)}`, {
        crossorigin: 'anonymous',
    });
}

/** Turn GPT's services on once per page, however many slots ask. */
export function enableGptServices(googletag: GoogleTag): void {
    if (!googletag.pubadsReady) {
        googletag.enableServices();
    }
}
