import { Head } from '@inertiajs/react';
import { AccountDirectory } from '@/components/admin/people/account-directory';
import type { PeoplePageProps } from '@/components/admin/people/types';

export default function AdminAdministratorsIndex(props: PeoplePageProps) {
    return (
        <>
            <Head title="Administrators" />
            <AccountDirectory
                {...props}
                indexUrl="/admin/administrators"
                storeUrl="/admin/administrators"
                routesFor={(account) => ({
                    show: `/admin/administrators/${account.id}`,
                    update: `/admin/administrators/${account.id}`,
                    destroy: `/admin/administrators/${account.id}`,
                    restore: `/admin/administrators/${account.id}/restore`,
                })}
            />
        </>
    );
}

AdminAdministratorsIndex.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Administrators', href: '/admin/administrators' },
    ],
};
