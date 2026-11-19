import { AdminSidebar } from '@/components/admin-sidebar';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { PageFade } from '@/components/motion/page-fade';
import { MotionProvider } from '@/components/motion/reveal';
import type { BreadcrumbItem } from '@/types';

export default function AdminLayout({
    breadcrumbs = [
        {
            title: 'Administration',
            href: '/admin',
        },
    ],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <MotionProvider>
            <AppShell variant="sidebar">
                <AdminSidebar />
                <AppContent
                    variant="sidebar"
                    className="min-w-0 overflow-x-clip bg-white"
                >
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    <PageFade>{children}</PageFade>
                </AppContent>
            </AppShell>
        </MotionProvider>
    );
}
