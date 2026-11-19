import { AccountDetailPage } from '@/components/admin/people/account-detail-page';
import type { PeopleDetailProps } from '@/components/admin/people/types';

export default function AdminAdministratorShow(props: PeopleDetailProps) {
    return <AccountDetailPage {...props} />;
}

AdminAdministratorShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Administrators', href: '/admin/administrators' },
        { title: 'Details', href: '#' },
    ],
};
