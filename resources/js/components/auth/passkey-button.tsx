import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { Fingerprint } from 'lucide-react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

type PasskeyRoutes = {
    options: UrlMethodPair;
    submit: UrlMethodPair;
};

/**
 * Starts the browser's passkey prompt. `icon` is the compact square button that sits next to the Log in button,
 * `wide` is a full-width button for the confirm screen. Renders nothing when the browser has no WebAuthn.
 */
export function PasskeyButton({
    variant = 'icon',
    routes,
    label = 'Sign in with a passkey',
    onError,
    className,
}: {
    variant?: 'icon' | 'wide';
    routes?: PasskeyRoutes;
    label?: string;
    onError?: (message: string | null) => void;
    className?: string;
}) {
    const { verify, isLoading, error, isSupported } = usePasskeyVerify({
        ...(routes && {
            routes: {
                options: routes.options.url,
                submit: routes.submit.url,
            },
        }),
        onSuccess: (response) => {
            router.visit(response.redirect ?? '/');
        },
    });

    useEffect(() => {
        onError?.(error ?? null);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [error]);

    if (!isSupported) {
        return null;
    }

    const start = async () => {
        await verify();
    };

    if (variant === 'wide') {
        return (
            <div className="grid gap-2">
                <Button
                    type="button"
                    onClick={start}
                    disabled={isLoading}
                    className={cn('h-11 w-full rounded-xl bg-ink text-white hover:bg-ink-soft', className)}
                >
                    {isLoading ? <Spinner /> : <Fingerprint className="size-5" />}
                    {isLoading ? 'Waiting for your device...' : label}
                </Button>
                {error ? (
                    <p role="alert" className="text-center text-sm font-medium text-brand-deep">
                        {error}
                    </p>
                ) : null}
            </div>
        );
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={start}
                    disabled={isLoading}
                    aria-label={label}
                    className={cn(
                        'size-11 shrink-0 rounded-xl border-line bg-sky-wash text-ink hover:bg-sky-pale',
                        error && 'border-brand text-brand-deep',
                        className,
                    )}
                >
                    {isLoading ? <Spinner /> : <Fingerprint className="size-5" />}
                </Button>
            </TooltipTrigger>
            <TooltipContent>{error ?? label}</TooltipContent>
        </Tooltip>
    );
}
