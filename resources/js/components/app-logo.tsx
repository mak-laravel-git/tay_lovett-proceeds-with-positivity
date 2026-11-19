import { usePage } from '@inertiajs/react';
import { LogoSquare } from '@/components/brand-mark';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <LogoSquare className="size-8 rounded-[10px] sm:size-8" />
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="truncate leading-tight font-extrabold">
                    {name}
                </span>
                <small className="text-[10px] tracking-wide text-muted-foreground">
                    PLATFORM ADMIN
                </small>
            </div>
        </>
    );
}
