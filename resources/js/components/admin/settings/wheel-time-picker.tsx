import { useEffect, useRef, useState } from 'react';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';

const ROW_HEIGHT = 36;
const VISIBLE_ROWS = 5;
const PAD = Math.floor(VISIBLE_ROWS / 2);

function Wheel({
    values,
    value,
    onChange,
    label,
}: {
    values: string[];
    value: number;
    onChange: (index: number) => void;
    label: string;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const timer = useRef<number | null>(null);
    const [dragging, setDragging] = useState(false);

    useEffect(() => {
        const el = ref.current;

        if (el && !dragging) {
            el.scrollTo({ top: value * ROW_HEIGHT, behavior: 'smooth' });
        }
    }, [value, dragging]);

    const settle = () => {
        const el = ref.current;

        if (!el) {
            return;
        }

        const index = Math.round(el.scrollTop / ROW_HEIGHT);
        const clamped = Math.min(values.length - 1, Math.max(0, index));

        el.scrollTo({ top: clamped * ROW_HEIGHT, behavior: 'smooth' });

        if (clamped !== value) {
            onChange(clamped);
        }
    };

    const onScroll = () => {
        setDragging(true);

        if (timer.current) {
            window.clearTimeout(timer.current);
        }

        timer.current = window.setTimeout(() => {
            setDragging(false);
            settle();
        }, 110);
    };

    return (
        <div className="relative" aria-label={label}>
            <div
                ref={ref}
                onScroll={onScroll}
                className="scrollbar-none h-[180px] snap-y snap-mandatory overflow-y-scroll overscroll-contain"
                style={{ paddingBlock: PAD * ROW_HEIGHT }}
            >
                {values.map((text, index) => (
                    <button
                        type="button"
                        key={text}
                        onClick={() => {
                            onChange(index);
                            ref.current?.scrollTo({ top: index * ROW_HEIGHT, behavior: 'smooth' });
                        }}
                        className={cn(
                            'flex h-9 w-full snap-center items-center justify-center text-lg font-bold tabular-nums transition-all',
                            index === value ? 'scale-100 text-ink opacity-100' : 'scale-90 text-muted-ink opacity-40',
                        )}
                    >
                        {text}
                    </button>
                ))}
            </div>
            <div className="pointer-events-none absolute inset-x-0 top-1/2 h-9 -translate-y-1/2 rounded-lg border-y-2 border-brand/25 bg-brand/5" />
        </div>
    );
}

const hours = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
const minutes = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));

/**
 * An iOS-style scrolling wheel for picking a time of day (24-hour, HH:mm). Each column snaps to the nearest
 * row and can be scrolled, dragged or tapped.
 */
export function WheelTimePicker({
    value,
    onChange,
}: {
    /** "HH:mm" */
    value: string;
    onChange: (value: string) => void;
}) {
    const [h, m] = value.split(':');
    const hourIndex = Math.max(0, hours.indexOf(h ?? '00'));
    const minuteIndex = Math.max(0, minutes.indexOf(m ?? '00'));

    return (
        <div className="flex items-center justify-center gap-1 rounded-2xl border border-line bg-white p-3">
            <Wheel
                values={hours}
                value={hourIndex}
                onChange={(index) => onChange(`${hours[index]}:${minutes[minuteIndex]}`)}
                label="Hour"
            />
            <span className="pb-1 text-xl font-black text-ink">:</span>
            <Wheel
                values={minutes}
                value={minuteIndex}
                onChange={(index) => onChange(`${hours[hourIndex]}:${minutes[index]}`)}
                label="Minute"
            />
        </div>
    );
}
