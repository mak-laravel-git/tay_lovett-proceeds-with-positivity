import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, TriangleAlert } from 'lucide-react';
import { BrandMark } from '@/components/brand-mark';
import { Reveal } from '@/components/motion/reveal';

export function LegalDocument({
    title,
    placeholder = false,
    updatedAt = null,
    children,
}: {
    title: string;
    /** True while the page still shows the built-in starter text. */
    placeholder?: boolean;
    updatedAt?: string | null;
    children: React.ReactNode;
}) {
    const { site } = usePage().props;

    return (
        <div className="flex min-h-dvh flex-col bg-canvas text-ink">
            <Head title={title} />
            <header className="flex h-17.5 items-center justify-between border-b-2 border-brand bg-night-1 px-4.5 text-white min-[761px]:h-19.5 min-[761px]:px-[3vw]">
                <BrandMark />
                <Link
                    href="/login"
                    className="rounded-xl bg-brand px-4 py-2.5 text-sm font-extrabold text-white hover:bg-brand-hover"
                >
                    Log in
                </Link>
            </header>
            <main className="mx-auto w-full max-w-242.5 flex-1 px-5.5 pt-16 pb-24">
                <Reveal>
                    <p className="text-xs font-black tracking-[2.3px] text-brand-deep uppercase">
                        Information
                    </p>
                    <h1 className="mt-2 text-[clamp(32px,4vw,48px)] font-black">
                        {title}
                    </h1>
                    {updatedAt ? (
                        <p className="mt-1 text-sm text-muted-ink">
                            Last updated{' '}
                            {new Date(updatedAt).toLocaleDateString(undefined, {
                                dateStyle: 'long',
                            })}
                        </p>
                    ) : null}
                    {placeholder ? (
                        <p className="mt-2 flex items-start gap-2 rounded-xl border border-brand/30 bg-brand-soft p-4 text-sm text-brand-deep">
                            <TriangleAlert className="mt-0.5 size-4 shrink-0" />
                            This is a starter outline and has not been reviewed
                            by a lawyer.
                        </p>
                    ) : null}
                    <div className="mt-6 rounded-2xl border bg-white p-7 leading-7 text-muted-ink">
                        {children}
                    </div>
                    <Link
                        className="mt-6 inline-flex items-center gap-2 text-sm font-bold text-brand-deep underline"
                        href="/login"
                    >
                        <ArrowLeft className="size-4" /> Return to login
                    </Link>
                </Reveal>
            </main>
            <footer className="bg-night-1 px-5 py-6 text-center text-xs text-mist-dim">
                {site.footer_text || site.name}
            </footer>
        </div>
    );
}
