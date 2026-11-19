import { motion, MotionConfig, type HTMLMotionProps } from 'motion/react';
import type { ReactNode } from 'react';

type RevealProps = {
    children: ReactNode;
    delay?: number;
    /** Distance in px the element slides up from. */
    y?: number;
} & Omit<HTMLMotionProps<'div'>, 'children'>;

/** Slides an element up and fades it in the first time it scrolls into view. */
export function Reveal({ children, delay = 0, y = 24, ...props }: RevealProps) {
    return (
        <motion.div
            initial={{ opacity: 0, transform: `translateY(${y}px)` }}
            whileInView={{ opacity: 1, transform: 'translateY(0px)' }}
            viewport={{ once: true, margin: '0px 0px -60px 0px' }}
            transition={{ duration: 0.45, ease: 'easeOut', delay }}
            {...props}
        >
            {children}
        </motion.div>
    );
}

const stagger = {
    hidden: {},
    show: { transition: { staggerChildren: 0.08 } },
};

const item = {
    hidden: { opacity: 0, transform: 'translateY(20px)' },
    show: {
        opacity: 1,
        transform: 'translateY(0px)',
        transition: { duration: 0.4, ease: 'easeOut' as const },
    },
};

/** Container that reveals its `StaggerItem` children one after another on scroll. */
export function Stagger({
    children,
    ...props
}: { children: ReactNode } & Omit<HTMLMotionProps<'div'>, 'children'>) {
    return (
        <motion.div
            variants={stagger}
            initial="hidden"
            whileInView="show"
            viewport={{ once: true, margin: '0px 0px -60px 0px' }}
            {...props}
        >
            {children}
        </motion.div>
    );
}

export function StaggerItem({
    children,
    ...props
}: { children: ReactNode } & Omit<HTMLMotionProps<'div'>, 'children'>) {
    return (
        <motion.div variants={item} {...props}>
            {children}
        </motion.div>
    );
}

/** Honors the OS "reduce motion" setting for every motion component below it. */
export function MotionProvider({ children }: { children: ReactNode }) {
    return <MotionConfig reducedMotion="user">{children}</MotionConfig>;
}
