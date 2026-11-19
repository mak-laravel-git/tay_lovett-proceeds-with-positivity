import { usePage } from '@inertiajs/react';
import { Sparkle } from 'lucide-react';
import { cn } from '@/lib/utils';

export function LogoSquare({ className }: { className?: string }) {
    const { site } = usePage().props;

    if (site?.logo_url) {
        return (
            <img
                src={site.logo_url}
                alt=""
                className={cn(
                    'size-9 shrink-0 rounded-[14px] bg-white object-contain sm:size-10',
                    className,
                )}
            />
        );
    }

    return (
        <span
            className={cn(
                'grid size-9 shrink-0 place-items-center rounded-[14px] bg-logo text-ink shadow-[0_0_0_2px_color-mix(in_srgb,var(--color-brand)_23%,transparent)] sm:size-10',
                className,
            )}
        >
            <Sparkle className="size-5 fill-current" aria-hidden />
        </span>
    );
}

export function BrandMark({
    name,
    tagline = true,
    className,
}: {
    name?: string;
    tagline?: boolean;
    className?: string;
}) {
    const { site } = usePage().props;

    return (
        <div className={cn('flex min-w-0 items-center gap-3', className)}>
            <LogoSquare />
            <div className="min-w-0 leading-tight">
                <b className="block truncate text-[13px] font-extrabold sm:text-[15px]">
                    {name ?? site.name}
                </b>
                {tagline && site.tagline ? (
                    <small className="hidden text-[10px] tracking-[0.9px] text-sky min-[361px]:block">
                        {site.tagline}
                    </small>
                ) : null}
            </div>
        </div>
    );
}
