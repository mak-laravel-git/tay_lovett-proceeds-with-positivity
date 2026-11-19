import { CalendarClock } from 'lucide-react';
import { useState } from 'react';
import { WheelTimePicker } from '@/components/admin/settings/wheel-time-picker';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';

function toLocalParts(iso: string): { date: Date | undefined; time: string } {
    if (!iso) {
        return { date: undefined, time: '00:00' };
    }

    const parsed = new Date(iso);

    if (Number.isNaN(parsed.getTime())) {
        return { date: undefined, time: '00:00' };
    }

    const time = `${String(parsed.getHours()).padStart(2, '0')}:${String(parsed.getMinutes()).padStart(2, '0')}`;

    return { date: parsed, time };
}

function toIso(date: Date, time: string): string {
    const [hours, minutes] = time.split(':').map(Number);
    const next = new Date(date);
    next.setHours(hours ?? 0, minutes ?? 0, 0, 0);

    const pad = (n: number) => String(n).padStart(2, '0');

    return `${next.getFullYear()}-${pad(next.getMonth() + 1)}-${pad(next.getDate())}T${pad(next.getHours())}:${pad(next.getMinutes())}`;
}

/**
 * A date and time picker combining a shadcn calendar with an iOS-style scrolling wheel for the time. The value
 * and onChange use the same "YYYY-MM-DDTHH:mm" format as a native datetime-local input.
 */
export function DateTimePicker({
    value,
    onChange,
    placeholder = 'Pick a date and time',
    minDate,
}: {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    minDate?: Date;
}) {
    const [open, setOpen] = useState(false);
    const parts = toLocalParts(value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className="h-11 w-full justify-start gap-2 rounded-xl border-line bg-white font-normal"
                >
                    <CalendarClock className="size-4 text-muted-ink" />
                    {parts.date
                        ? `${parts.date.toLocaleDateString(undefined, { dateStyle: 'medium' })} · ${parts.time}`
                        : placeholder}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <div className="flex flex-col sm:flex-row">
                    <Calendar
                        mode="single"
                        selected={parts.date}
                        onSelect={(date) => date && onChange(toIso(date, parts.time))}
                        disabled={minDate ? { before: minDate } : undefined}
                        autoFocus
                    />
                    <Separator orientation="vertical" className="hidden h-auto sm:block" />
                    <Separator className="sm:hidden" />
                    <div className="grid place-items-center gap-2 p-3">
                        <p className="text-xs font-bold text-muted-ink">Time</p>
                        <WheelTimePicker
                            value={parts.time}
                            onChange={(time) => onChange(toIso(parts.date ?? new Date(), time))}
                        />
                    </div>
                </div>
                <div className="flex justify-end border-t border-line p-2">
                    <Button type="button" size="sm" onClick={() => setOpen(false)}>
                        Done
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
