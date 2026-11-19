import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function Pill({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-block rounded-full bg-brand-soft px-2.5 py-1 text-[10px] font-extrabold text-brand-deep',
                className,
            )}
        >
            {children}
        </span>
    );
}

export function PageTitle({
    icon: Icon,
    title,
    pill,
}: {
    icon?: React.ComponentType<{ className?: string }>;
    title: string;
    pill?: string;
}) {
    return (
        <div className="mb-4 flex items-center justify-between gap-3 px-3.5 pt-2 md:px-0 md:pt-0">
            <h1 className="flex items-center gap-2 text-xl font-extrabold md:text-2xl">
                {Icon && <Icon className="size-5 md:size-6" />}
                {title}
            </h1>
            {pill && <Pill>{pill}</Pill>}
        </div>
    );
}

export function Panel({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <section
            className={cn(
                'mb-3 overflow-hidden border-y bg-card shadow-none md:mb-4 md:rounded-2xl md:border md:shadow-[0_6px_25px_rgba(6,23,35,0.08)]',
                className,
            )}
        >
            {children}
        </section>
    );
}

export function Metric({
    label,
    value,
    note,
}: {
    label: string;
    value: string;
    note?: string;
}) {
    return (
        <div className="rounded-2xl border bg-card p-4 shadow-[0_6px_25px_rgba(6,23,35,0.08)]">
            <small className="block text-xs text-muted-foreground">
                {label}
            </small>
            <strong className="my-1 block text-2xl font-extrabold text-brand-deep">
                {value}
            </strong>
            {note && (
                <em className="text-[11px] text-success not-italic">{note}</em>
            )}
        </div>
    );
}

export function Notice({ children }: { children: ReactNode }) {
    return (
        <div className="rounded-xl border border-l-4 border-sky/60 border-l-brand bg-sky-pale p-3 text-xs leading-relaxed text-ink">
            {children}
        </div>
    );
}
