import { CircleAlert, CircleCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/** Shared input look for every auth screen: tall, rounded and on a white surface. */
export const authInput = 'h-11 rounded-xl border-line bg-white text-[15px]';

export const authButton =
    'h-11 rounded-xl bg-brand text-[15px] font-extrabold text-white hover:bg-brand-hover';

export function AuthField({
    id,
    label,
    error,
    aside,
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    aside?: ReactNode;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <div className="flex items-center justify-between gap-3">
                <Label htmlFor={id} className="font-bold text-ink">
                    {label}
                </Label>
                {aside}
            </div>
            {children}
            {hint && !error ? (
                <p className="text-xs text-muted-ink">{hint}</p>
            ) : null}
            {error ? (
                <p role="alert" className="text-sm font-medium text-brand-deep">
                    {error}
                </p>
            ) : null}
        </div>
    );
}

export function AuthNotice({
    tone = 'success',
    children,
}: {
    tone?: 'success' | 'error';
    children: ReactNode;
}) {
    const Icon = tone === 'success' ? CircleCheck : CircleAlert;

    return (
        <div
            role={tone === 'error' ? 'alert' : 'status'}
            className={cn(
                'flex items-start gap-2 rounded-xl border px-3 py-2.5 text-sm',
                tone === 'success'
                    ? 'border-success/30 bg-success/10 text-ink'
                    : 'border-brand/30 bg-brand-soft text-brand-deep',
            )}
        >
            <Icon className="mt-0.5 size-4 shrink-0" aria-hidden />
            <span>{children}</span>
        </div>
    );
}
