import { useCallback, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { AdGate } from '@/components/ads/ad-gate';
import { fetchGate } from '@/lib/ads-api';
import type { GatePurpose } from '@/lib/ads-api';

/**
 * Put any action behind a completed ad / survey:
 *
 *   const gate = useAdGate('quote_generation');
 *   <Button onClick={() => gate.run(() => form.post('/studio'))}>Create quote</Button>
 *   {gate.element}
 *
 * run() first asks the server whether the user already holds an unlock; if so (or the gate is switched off) the
 * action runs straight away, otherwise the survey dialog opens and the action runs once the provider confirms.
 * This is a convenience only: the server re-checks and is what actually refuses the action.
 */
export function useAdGate(purpose: GatePurpose): {
    run: (action: () => void) => Promise<void>;
    checking: boolean;
    element: ReactNode;
} {
    const [open, setOpen] = useState(false);
    const [checking, setChecking] = useState(false);
    const pending = useRef<(() => void) | null>(null);

    const run = useCallback(
        async (action: () => void) => {
            setChecking(true);

            try {
                const status = await fetchGate(purpose);

                if (!status.enabled || status.unlocked) {
                    action();

                    return;
                }
            } catch {
                // Could not check: show the gate. The server decides either way.
            } finally {
                setChecking(false);
            }

            pending.current = action;
            setOpen(true);
        },
        [purpose],
    );

    const element = (
        <AdGate
            purpose={purpose}
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (!next) {
                    pending.current = null;
                }
            }}
            onUnlocked={() => {
                const action = pending.current;

                pending.current = null;
                setOpen(false);
                action?.();
            }}
        />
    );

    return { run, checking, element };
}
