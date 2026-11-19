import { usePage } from '@inertiajs/react';
import { motion } from 'motion/react';
import type { ReactNode } from 'react';

export function PageFade({ children }: { children: ReactNode }) {
    const { component } = usePage();

    return (
        <motion.div
            key={component}
            className="min-w-0"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.25, ease: 'easeOut' }}
        >
            {children}
        </motion.div>
    );
}
