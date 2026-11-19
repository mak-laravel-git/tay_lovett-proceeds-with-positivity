import { Head } from '@inertiajs/react';
import { AccountDirectory } from '@/components/admin/people/account-directory';
import type { PeoplePageProps } from '@/components/admin/people/types';

export default function AdminUsersIndex(props: PeoplePageProps) {
    return (
        <>
            <Head title="Users" />
            <AccountDirectory
                {...props}
                indexUrl="/admin/users"
                storeUrl="/admin/users"
                routesFor={(account) => ({
                    show: `/admin/users/${account.id}`,
                    update: `/admin/users/${account.id}`,
                    destroy: `/admin/users/${account.id}`,
                    restore: `/admin/users/${account.id}/restore`,
                    ban: `/admin/users/${account.id}/ban`,
                    unban: `/admin/users/${account.id}/unban`,
                    suspend: `/admin/users/${account.id}/suspend`,
                    unsuspend: `/admin/users/${account.id}/unsuspend`,
                })}
            />
        </>
    );
}

AdminUsersIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Users', href: '/admin/users' },
    ],
};
