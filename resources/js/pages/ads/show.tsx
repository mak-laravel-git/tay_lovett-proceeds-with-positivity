import { Form, Head } from '@inertiajs/react';
import { ArrowRight, Sun, X } from 'lucide-react';
import { motion } from 'motion/react';
import { MotionProvider } from '@/components/motion/reveal';
import { Button } from '@/components/ui/button';

export default function DemoAd({ ad }: { ad: { id: number; label: string } }) {
    return (
        <MotionProvider>
            <main className="flex h-dvh w-screen flex-col bg-ink text-white">
                <Head title="Demonstration advertisement" />
                <div className="flex items-center justify-between border-b border-night-4 p-[clamp(14px,2vw,22px)]">
                    <strong className="text-sm">
                        Sponsored · Full-screen ad preview
                    </strong>
                    <Form action={`/ads/${ad.id}/exit`} method="post">
                        <button
                            type="submit"
                            aria-label="Close advertisement"
                            className="grid size-[34px] place-items-center rounded-[9px] bg-night-4 text-white hover:bg-night-edge focus-visible:ring-2 focus-visible:ring-sky focus-visible:outline-none"
                        >
                            <X className="size-5" />
                        </button>
                    </Form>
                </div>
                <motion.div
                    className="flex min-h-0 flex-1 flex-col justify-center p-[clamp(18px,4vw,48px)]"
                    initial={{ opacity: 0, transform: 'translateY(32px)' }}
                    animate={{ opacity: 1, transform: 'translateY(0px)' }}
                    transition={{ duration: 0.5, ease: 'easeOut' }}
                >
                    <div className="mx-auto w-[min(100%,860px)] rounded-[14px] bg-ad px-4 py-12 text-center min-[761px]:py-[clamp(34px,7vw,110px)]">
                        <Sun className="mx-auto size-11" aria-hidden />
                        <h1 className="mt-1 mb-1.5 text-[clamp(27px,5vw,58px)] font-black">
                            Choose a brighter day
                        </h1>
                        <p className="text-[clamp(13px,1.7vw,18px)] text-mist-soft">
                            {ad.label}
                        </p>
                    </div>
                    <p className="mt-4 text-center text-xs text-mist-dim">
                        Demo only · No ad network connected · No earnings
                        generated
                    </p>
                </motion.div>
                <Form
                    action={`/ads/${ad.id}/exit`}
                    method="post"
                    className="bg-night-5 px-[max(22px,calc((100vw-850px)/2))] pt-3.5 pb-[calc(18px+env(safe-area-inset-bottom))]"
                >
                    <Button
                        type="submit"
                        className="h-auto w-full rounded-[10px] bg-brand py-4 text-base font-black text-white hover:bg-brand-hover"
                    >
                        Continue to next quote <ArrowRight />
                    </Button>
                </Form>
            </main>
        </MotionProvider>
    );
}
