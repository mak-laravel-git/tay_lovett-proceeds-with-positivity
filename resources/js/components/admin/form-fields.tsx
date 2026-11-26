import type { ReactNode } from 'react';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';
import type { SelectOption } from '@/types/ads';

/** Small labelled controls shared by the admin configuration forms. */

export const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export function Field({
    label,
    hint,
    error,
    className,
    children,
}: {
    label: string;
    hint?: string;
    error?: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <label className={cn('grid content-start gap-1.5 text-sm', className)}>
            <span className="font-medium">{label}</span>
            {children}
            {hint ? (
                <span className="text-xs text-muted-foreground">{hint}</span>
            ) : null}
            {error ? (
                <span className="text-xs text-destructive" role="alert">
                    {error}
                </span>
            ) : null}
        </label>
    );
}

export function SelectField({
    label,
    hint,
    error,
    value,
    options,
    disabled,
    onChange,
}: {
    label: string;
    hint?: string;
    error?: string;
    value: string;
    options: SelectOption[];
    disabled?: boolean;
    onChange: (value: string) => void;
}) {
    return (
        <Field label={label} hint={hint} error={error}>
            <select
                className={selectClass}
                value={value}
                disabled={disabled}
                onChange={(event) => onChange(event.target.value)}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </Field>
    );
}

export function SwitchField({
    id,
    label,
    hint,
    checked,
    onChange,
}: {
    id: string;
    label: string;
    hint?: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <div className="flex items-start gap-3">
            <Switch id={id} checked={checked} onCheckedChange={onChange} />
            <div className="grid gap-0.5">
                <Label htmlFor={id}>{label}</Label>
                {hint ? (
                    <p className="text-xs text-muted-foreground">{hint}</p>
                ) : null}
            </div>
        </div>
    );
}
