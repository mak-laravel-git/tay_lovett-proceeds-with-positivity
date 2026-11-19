import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * One titled card on the Profile and Security pages. It uses only brand tokens, so it looks right
 * inside both the user layout and the admin layout.
 */
export function AccountSection({
    title,
    description,
    icon,
    tone = 'default',
    actions,
    children,
}: {
    title: string;
    description?: string;
    icon?: ReactNode;
    tone?: 'default' | 'danger';
    actions?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section
            className={cn(
                'overflow-hidden rounded-2xl border bg-white shadow-[0_6px_25px_rgba(6,23,35,0.06)]',
                tone === 'danger' ? 'border-brand/30' : 'border-line',
            )}
        >
            <div
                className={cn(
                    'h-1.5',
                    tone === 'danger'
                        ? 'bg-brand'
                        : 'bg-gradient-to-r from-sky to-brand',
                )}
            />
            <header className="flex flex-wrap items-start justify-between gap-3 px-5 pt-4 pb-3">
                <div className="flex min-w-0 items-start gap-3">
                    {icon ? (
                        <span
                            className={cn(
                                'grid size-9 shrink-0 place-items-center rounded-xl',
                                tone === 'danger'
                                    ? 'bg-brand-soft text-brand-deep'
                                    : 'bg-sky-pale text-ink',
                            )}
                        >
                            {icon}
                        </span>
                    ) : null}
                    <div className="min-w-0">
                        <h2 className="text-base font-extrabold text-ink">{title}</h2>
                        {description ? (
                            <p className="mt-0.5 text-sm text-muted-ink">{description}</p>
                        ) : null}
                    </div>
                </div>
                {actions}
            </header>
            <div className="px-5 pb-5">{children}</div>
        </section>
    );
}

export function FieldRow({
    label,
    htmlFor,
    error,
    hint,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <label htmlFor={htmlFor} className="text-sm font-bold text-ink">
                {label}
            </label>
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

export const accountInput = 'h-11 rounded-xl border-line bg-white';
export const accountButton =
    'h-10 rounded-xl bg-brand px-5 font-extrabold text-white hover:bg-brand-hover';
