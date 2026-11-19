import { Head } from '@inertiajs/react';

export default function Placeholder({ title }: { title: string }) {
    return (
        <>
            <Head title={title} />
            <section className="rounded-2xl border bg-card p-6">
                <h1 className="text-2xl font-black">{title}</h1>
                <p className="mt-2 text-sm text-muted-foreground">
                    This section is ready for the next workflow.
                </p>
            </section>
        </>
    );
}
