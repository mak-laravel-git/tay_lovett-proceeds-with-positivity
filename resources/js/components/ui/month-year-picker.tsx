import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

const MONTHS = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
] as const;

const START_YEAR = 2026;

function parseMonthValue(value: string): { year: number; month: number } {
    const [yearPart, monthPart] = value.split('-');
    const year = Number(yearPart);
    const month = Number(monthPart);

    return {
        year: Number.isFinite(year) ? year : new Date().getFullYear(),
        month: Number.isFinite(month) ? month : 1,
    };
}

function formatMonthValue(year: number, month: number): string {
    return `${year}-${String(month).padStart(2, '0')}`;
}

function monthLabel(year: number, month: number): string {
    return new Date(year, month - 1, 1).toLocaleString('en-US', {
        month: 'long',
        year: 'numeric',
    });
}

type MonthYearPickerProps = {
    value: string;
    onChange: (value: string) => void;
    className?: string;
    align?: 'start' | 'center' | 'end';
};

export function MonthYearPicker({
    value,
    onChange,
    className,
    align = 'end',
}: MonthYearPickerProps) {
    const current = new Date();
    const currentYear = current.getFullYear();
    const currentMonth = current.getMonth() + 1;
    const maxYear = Math.max(START_YEAR, currentYear);

    const selected = parseMonthValue(value);
    const [open, setOpen] = useState(false);
    const [browseYear, setBrowseYear] = useState(selected.year);

    const years = useMemo(() => {
        const list: number[] = [];

        for (let year = maxYear; year >= START_YEAR; year -= 1) {
            list.push(year);
        }

        return list;
    }, [maxYear]);

    function isMonthDisabled(month: number): boolean {
        if (browseYear > currentYear) {
            return true;
        }

        if (browseYear === currentYear && month > currentMonth) {
            return true;
        }

        return false;
    }

    return (
        <DropdownMenu
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                if (next) {
                    setBrowseYear(selected.year);
                }
            }}
        >
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className={cn(
                        'h-9 min-w-[10.5rem] justify-between border-[#c7dfe8] bg-white font-medium text-[#0a1018]',
                        className,
                    )}
                >
                    <span className="inline-flex items-center gap-2">
                        <CalendarDays className="size-4 text-[#0f6f8f]" />
                        {monthLabel(selected.year, selected.month)}
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align={align}
                className="w-80 p-3"
                onCloseAutoFocus={(event) => event.preventDefault()}
            >
                <div className="mb-3 flex items-center gap-2">
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="size-8"
                        disabled={browseYear <= START_YEAR}
                        onClick={() =>
                            setBrowseYear((year) => Math.max(START_YEAR, year - 1))
                        }
                        aria-label="Previous year"
                    >
                        <ChevronLeft className="size-4" />
                    </Button>

                    <Select
                        value={String(browseYear)}
                        onValueChange={(next) => setBrowseYear(Number(next))}
                    >
                        <SelectTrigger
                            className="h-8 flex-1 border-[#c7dfe8] bg-white font-semibold"
                            aria-label="Year"
                        >
                            <SelectValue placeholder="Year" />
                        </SelectTrigger>
                        <SelectContent position="popper">
                            {years.map((year) => (
                                <SelectItem key={year} value={String(year)}>
                                    {year}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="size-8"
                        disabled={browseYear >= maxYear}
                        onClick={() =>
                            setBrowseYear((year) => Math.min(maxYear, year + 1))
                        }
                        aria-label="Next year"
                    >
                        <ChevronRight className="size-4" />
                    </Button>
                </div>

                <div className="grid grid-cols-3 gap-1.5">
                    {MONTHS.map((month) => {
                        const disabled = isMonthDisabled(month.value);
                        const active =
                            selected.year === browseYear &&
                            selected.month === month.value;

                        return (
                            <Button
                                key={month.value}
                                type="button"
                                variant={active ? 'default' : 'outline'}
                                disabled={disabled}
                                className={cn(
                                    'h-9 px-1 text-xs font-medium',
                                    active &&
                                        'bg-[#ed2948] text-white hover:bg-[#d61f3d]',
                                    !active &&
                                        'border-[#d4e7ed] bg-white text-[#0a1018] hover:border-[#76d8f8] hover:bg-[#e7f7ff]',
                                )}
                                onClick={() => {
                                    onChange(
                                        formatMonthValue(
                                            browseYear,
                                            month.value,
                                        ),
                                    );
                                    setOpen(false);
                                }}
                            >
                                {month.label}
                            </Button>
                        );
                    })}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
