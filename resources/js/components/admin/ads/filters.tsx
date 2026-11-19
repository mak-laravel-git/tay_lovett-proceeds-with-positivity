import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { SelectOption } from '@/types/ads';

type FilterValues = Record<string, string>;

/**
 * Server-side filters for an admin table: values live in local state, "Apply" sends them as query parameters
 * (so the URL is shareable and pagination keeps them), and "Reset" clears them.
 */
export function useFilters<T extends FilterValues>(path: string, initial: T) {
    const [values, setValues] = useState<T>(initial);

    const visit = (next: Partial<FilterValues>) => {
        const query = Object.fromEntries(
            Object.entries(next).filter(([, value]) => value !== ''),
        );

        router.get(path, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return {
        values,
        set: (key: keyof T, value: string) =>
            setValues((current) => ({ ...current, [key]: value })),
        apply: () => visit(values),
        reset: () => {
            const empty = Object.fromEntries(
                Object.keys(initial).map((key) => [key, '']),
            ) as T;
            setValues(empty);
            visit({});
        },
        active: Object.values(values).some((value) => value !== ''),
    };
}

export function FilterBar({
    children,
    onApply,
    onReset,
    active,
}: {
    children: ReactNode;
    onApply: () => void;
    onReset: () => void;
    active: boolean;
}) {
    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                onApply();
            }}
            className="mb-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
        >
            {children}
            <div className="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                <Button type="submit" size="sm">
                    Apply filters
                </Button>
                {active ? (
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={onReset}
                    >
                        Reset
                    </Button>
                ) : null}
            </div>
        </form>
    );
}

export function FilterField({
    label,
    children,
    className,
}: {
    label: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <label className={cn('grid gap-1 text-xs font-medium', className)}>
            <span className="text-[#526475]">{label}</span>
            {children}
        </label>
    );
}

export function TextFilter({
    label,
    value,
    onChange,
    placeholder,
    type = 'text',
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    type?: 'text' | 'date';
}) {
    return (
        <FilterField label={label}>
            <Input
                type={type}
                value={value}
                placeholder={placeholder}
                onChange={(event) => onChange(event.target.value)}
            />
        </FilterField>
    );
}

export function SelectFilter({
    label,
    value,
    onChange,
    options,
    anyLabel = 'Any',
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: SelectOption[];
    anyLabel?: string;
}) {
    return (
        <FilterField label={label}>
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <option value="">{anyLabel}</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </FilterField>
    );
}
