import { Head } from '@inertiajs/react';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export default function AdminPlaceholder({ title }: { title: string }) {
    return (
        <>
            <Head title={title} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>{title}</CardTitle>
                        <CardDescription>
                            This section is ready for the next workflow.
                        </CardDescription>
                    </CardHeader>
                </Card>
            </div>
        </>
    );
}

AdminPlaceholder.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Section', href: '/admin' },
    ],
};
