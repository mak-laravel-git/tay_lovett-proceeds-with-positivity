import * as DialogPrimitive from '@radix-ui/react-dialog';
import { XIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type RoundedPanelSide = 'right' | 'left';

type RoundedPanelProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    children: ReactNode;
    side?: RoundedPanelSide;
    /** Max width classes, e.g. `sm:max-w-md` or `sm:max-w-xl` */
    sizeClassName?: string;
    className?: string;
    showClose?: boolean;
};

/**
 * Reusable rounded side panel with dimmed overlay.
 * Use for drawers/sheets that should float inset with rounded corners.
 */
export function RoundedPanel({
    open,
    onOpenChange,
    children,
    side = 'right',
    sizeClassName = 'sm:max-w-lg',
    className,
    showClose = true,
}: RoundedPanelProps) {
    return (
        <DialogPrimitive.Root open={open} onOpenChange={onOpenChange}>
            <DialogPrimitive.Portal>
                <DialogPrimitive.Overlay
                    className={cn(
                        'fixed inset-0 z-50 bg-[#0a1018]/55 backdrop-blur-[2px]',
                        'data-[state=open]:animate-in data-[state=closed]:animate-out',
                        'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
                    )}
                />
                <DialogPrimitive.Content
                    className={cn(
                        'fixed z-50 flex flex-col overflow-hidden border border-[#d4e7ed] bg-white shadow-2xl outline-none',
                        'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:duration-200 data-[state=open]:duration-300',
                        side === 'right' &&
                            'inset-y-3 right-3 w-[min(100%-1.5rem,28rem)] rounded-2xl data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right',
                        side === 'left' &&
                            'inset-y-3 left-3 w-[min(100%-1.5rem,28rem)] rounded-2xl data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left',
                        sizeClassName,
                        className,
                    )}
                >
                    {showClose && (
                        <DialogPrimitive.Close className="absolute top-3 right-3 z-10 rounded-full p-1.5 text-[#526475] transition-colors hover:bg-[#e7f7ff] hover:text-[#0a1018] focus-visible:ring-2 focus-visible:ring-[#76d8f8] focus-visible:outline-none">
                            <XIcon className="size-4" />
                            <span className="sr-only">Close</span>
                        </DialogPrimitive.Close>
                    )}
                    {children}
                </DialogPrimitive.Content>
            </DialogPrimitive.Portal>
        </DialogPrimitive.Root>
    );
}

export function RoundedPanelHeader({
    className,
    ...props
}: React.ComponentProps<'div'>) {
    return (
        <div
            className={cn('border-b border-[#e7f3f7] px-5 py-4 pr-12', className)}
            {...props}
        />
    );
}

export function RoundedPanelBody({
    className,
    ...props
}: React.ComponentProps<'div'>) {
    return (
        <div
            className={cn('flex-1 overflow-y-auto px-5 py-4', className)}
            {...props}
        />
    );
}

export function RoundedPanelFooter({
    className,
    ...props
}: React.ComponentProps<'div'>) {
    return (
        <div
            className={cn(
                'flex flex-wrap items-center justify-end gap-2 border-t border-[#e7f3f7] px-5 py-3',
                className,
            )}
            {...props}
        />
    );
}
