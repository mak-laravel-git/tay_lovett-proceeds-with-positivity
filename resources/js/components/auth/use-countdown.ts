import { useCallback, useEffect, useState } from 'react';

/**
 * Counts down from a number of seconds to zero, once per second. `restart` starts over from a new value.
 */
export function useCountdown(initialSeconds: number): [number, (seconds: number) => void] {
    const [seconds, setSeconds] = useState(Math.max(0, Math.ceil(initialSeconds)));

    useEffect(() => {
        if (seconds <= 0) {
            return;
        }

        const timer = window.setTimeout(() => setSeconds((value) => Math.max(0, value - 1)), 1000);

        return () => window.clearTimeout(timer);
    }, [seconds]);

    const restart = useCallback((next: number) => setSeconds(Math.max(0, Math.ceil(next))), []);

    return [seconds, restart];
}
