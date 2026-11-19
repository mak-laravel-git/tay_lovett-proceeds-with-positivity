import { motion, type HTMLMotionProps } from 'motion/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type FadeInProps = {
    children: ReactNode;
    className?: string;
    delay?: number;
} & Omit<HTMLMotionProps<'div'>, 'children'>;

export function FadeIn({
    children,
    className,
    delay = 0,
    ...props
}: FadeInProps) {
    return (
        <motion.div
            className={cn('min-w-0', className)}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.3, ease: 'easeOut', delay }}
            {...props}
        >
            {children}
        </motion.div>
    );
}
