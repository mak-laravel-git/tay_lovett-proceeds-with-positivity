import { useCallback, useState } from 'react';
import type { ReactNode } from 'react';
import { AdGate } from '@/components/ads/ad-gate';

/**
 * An ad break: load the survey, let the user complete it, and come back to the same page with the update done.
 * Nothing is unlocked and nothing navigates; the provider's confirmation is what awards points on the server.
 *
 *   const adBreak = useAdBreak({ onComplete: () => router.reload({ only: ['points'] }) });
 *   <Button onClick={adBreak.start}>Generate Ad</Button>
 *   {adBreak.element}
 */
export function useAdBreak({ onComplete }: { onComplete?: () => void } = {}): {
    start: () => void;
    element: ReactNode;
} {
    const [open, setOpen] = useState(false);

    const start = useCallback(() => setOpen(true), []);

    const element = (
        <AdGate
            open={open}
            onOpenChange={setOpen}
            onUnlocked={() => {
                setOpen(false);
                onComplete?.();
            }}
            title="Complete a survey"
            description="Finish one survey to earn points. You stay on this page. Points are added once the survey provider confirms it, which can take a few minutes."
        />
    );

    return { start, element };
}
