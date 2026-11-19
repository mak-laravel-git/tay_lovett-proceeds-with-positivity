import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AdminLayout from '@/layouts/admin-layout';
import UserLayout from '@/layouts/user-layout';

/**
 * Account pages (Profile, Security) are shared by everyone, but each role stays in its own workspace:
 * administrators get the admin sidebar layout and users get the user layout.
 */
export default function AccountShell({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;

    if (auth.is_administrator) {
        return (
            <AdminLayout
                breadcrumbs={[
                    { title: 'Administration', href: '/admin' },
                    { title: 'Account', href: '/settings/profile' },
                ]}
            >
                {children}
            </AdminLayout>
        );
    }

    return <UserLayout>{children}</UserLayout>;
}
