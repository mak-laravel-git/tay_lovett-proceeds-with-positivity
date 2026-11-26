import { usePage } from '@inertiajs/react';
import {
    useEffect,
    useId,
    useRef,
    useState,
    useSyncExternalStore,
} from 'react';
import { enableGptServices, loadAdSense, loadGpt } from '@/lib/google-ads';
import type { GptEvent, GptSlot } from '@/lib/google-ads';
import { cn } from '@/lib/utils';

/** Where a regular ad can be mounted. Mirrors App\Enums\AdLocation. */
export type AdLocation =
    | 'feed_top'
    | 'feed_between'
    | 'saved_top'
    | 'public_quote_below'
    | 'studio_below'
    | 'sidebar_right'
    | 'earn_top';

export type DisplayPlacement = {
    id: number;
    location: AdLocation;
    network: 'adsense' | 'ad_manager';
    ad_unit_path: string | null;
    adsense_slot_id: string | null;
    sizes: [number, number][];
    devices: 'all' | 'mobile' | 'desktop';
    min_height: number;
    every_n: number | null;
};

/** The shared `displayAds` prop; null when regular ads are off or the page must not carry any. */
export type DisplayAds = {
    client: string | null;
    test_mode: boolean;
    placements: DisplayPlacement[];
};

const PHONE = '(max-width: 760px)';
// Matches the user layout: the right sidebar only exists from this width.
const WIDE = '(min-width: 1191px)';

/** Whether a media query matches, or null during server rendering where ads are never drawn. */
function useMediaQuery(query: string): boolean | null {
    return useSyncExternalStore(
        (listener) => {
            const media = window.matchMedia(query);
            media.addEventListener('change', listener);

            return () => media.removeEventListener('change', listener);
        },
        () => window.matchMedia(query).matches,
        () => null,
    );
}

export function useDisplayPlacements(location: AdLocation): DisplayPlacement[] {
    const ads = usePage().props.displayAds as DisplayAds | null | undefined;
    const phone = useMediaQuery(PHONE);
    const wide = useMediaQuery(WIDE);
    const device = phone ? 'mobile' : 'desktop';

    // A unit inside a hidden column must not be requested at all.
    if (!ads || phone === null || (location === 'sidebar_right' && !wide)) {
        return [];
    }

    return ads.placements.filter(
        (placement) =>
            placement.location === location &&
            (placement.devices === 'all' || placement.devices === device),
    );
}

/**
 * The regular Google ads configured for one location. Renders nothing at all when there are none, so a page
 * never shows an empty box.
 */
export function DisplayAdSlot({
    location,
    className,
}: {
    location: AdLocation;
    className?: string;
}) {
    const ads = usePage().props.displayAds as DisplayAds | null | undefined;
    const placements = useDisplayPlacements(location);
    // A new page means new ad requests, so the units are remounted on navigation.
    const { url } = usePage();

    return placements.map((placement) => (
        <AdUnit
            key={`${placement.id}:${url}`}
            placement={placement}
            client={ads?.client ?? null}
            testMode={ads?.test_mode ?? false}
            className={className}
        />
    ));
}

type UnitProps = {
    placement: DisplayPlacement;
    client: string | null;
    testMode: boolean;
    className?: string;
};

function AdUnit({ placement, client, testMode, className }: UnitProps) {
    const [failed, setFailed] = useState(false);

    if (failed) {
        return null;
    }

    return (
        <aside
            aria-label="Advertisement"
            className={cn('my-4 min-w-0 text-center', className)}
        >
            <span className="mb-1 block text-[10px] tracking-wide text-muted-foreground uppercase">
                Advertisement
            </span>
            <div
                style={{ minHeight: placement.min_height || undefined }}
                className="overflow-hidden"
            >
                {placement.network === 'adsense' ? (
                    <AdSenseUnit
                        slotId={placement.adsense_slot_id}
                        client={client}
                        testMode={testMode}
                        onFail={() => setFailed(true)}
                    />
                ) : (
                    <AdManagerUnit
                        path={placement.ad_unit_path}
                        sizes={placement.sizes}
                        onFail={() => setFailed(true)}
                    />
                )}
            </div>
        </aside>
    );
}

function AdSenseUnit({
    slotId,
    client,
    testMode,
    onFail,
}: {
    slotId: string | null;
    client: string | null;
    testMode: boolean;
    onFail: () => void;
}) {
    const element = useRef<HTMLModElement>(null);
    const fail = useRef(onFail);

    useEffect(() => {
        const node = element.current;

        if (!node || !client || !slotId) {
            return;
        }

        // AdSense marks an element it could not fill; the whole block is then removed.
        const status = new MutationObserver(() => {
            if (node.dataset.adStatus === 'unfilled') {
                fail.current();
            }
        });
        status.observe(node, { attributeFilter: ['data-ad-status'] });

        loadAdSense(client)
            .then(() => {
                // One request per element, even if this effect runs twice.
                if (!node.isConnected || node.dataset.requested) {
                    return;
                }

                node.dataset.requested = 'true';
                (window.adsbygoogle = window.adsbygoogle ?? []).push({});
            })
            .catch(() => fail.current());

        return () => status.disconnect();
    }, [client, slotId]);

    return (
        <ins
            ref={element}
            className="adsbygoogle"
            style={{ display: 'block' }}
            data-ad-client={client ?? undefined}
            data-ad-slot={slotId ?? undefined}
            data-ad-format="auto"
            data-full-width-responsive="true"
            data-adtest={testMode ? 'on' : undefined}
        />
    );
}

function AdManagerUnit({
    path,
    sizes,
    onFail,
}: {
    path: string | null;
    sizes: [number, number][];
    onFail: () => void;
}) {
    const id = `gpt-${useId().replace(/[^a-zA-Z0-9_-]/g, '')}`;
    const fail = useRef(onFail);
    const sizeKey = JSON.stringify(sizes);

    useEffect(() => {
        if (!path) {
            return;
        }

        let cancelled = false;
        let slot: GptSlot | null = null;
        const rendered = (event: GptEvent) => {
            if (!cancelled && event.slot === slot && event.isEmpty) {
                fail.current();
            }
        };

        loadGpt()
            .then((googletag) => {
                if (cancelled) {
                    return;
                }

                const requested = JSON.parse(sizeKey) as [number, number][];
                slot = googletag.defineSlot(
                    path,
                    requested.length > 0 ? requested : [[300, 250]],
                    id,
                );

                if (slot) {
                    slot.addService(googletag.pubads());
                    googletag
                        .pubads()
                        .addEventListener('slotRenderEnded', rendered);
                    enableGptServices(googletag);
                    googletag.display(id);
                }
            })
            .catch(() => fail.current());

        return () => {
            cancelled = true;

            if (slot) {
                window.googletag
                    ?.pubads?.()
                    .removeEventListener('slotRenderEnded', rendered);
                window.googletag?.destroySlots?.([slot]);
            }
        };
    }, [path, sizeKey, id]);

    return <div id={id} className="mx-auto flex justify-center" />;
}
