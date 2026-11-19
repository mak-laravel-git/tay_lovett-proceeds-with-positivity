import { AccountDetailPage } from '@/components/admin/people/account-detail-page';
import type { PeopleDetailProps } from '@/components/admin/people/types';

export default function AdminUserShow(props: PeopleDetailProps) {
    return <AccountDetailPage {...props} />;
}

AdminUserShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Users', href: '/admin/users' },
        { title: 'Details', href: '#' },
    ],
};
