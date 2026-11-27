import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import AccountShell from '@/layouts/account-shell';
import AdminLayout from '@/layouts/admin-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import UserLayout from '@/layouts/user-layout';

// The server renders <title> with the site name from the database, so read it once at boot.
const appName =
    (typeof document !== 'undefined' ? document.title : '') ||
    import.meta.env.VITE_APP_NAME ||
    'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
            case name === 'maintenance':
            case name.startsWith('legal/'):
            case name.startsWith('help/'):
            case name.startsWith('ads/'):
            case name.startsWith('quotes/'):
            // The survey return page is shown inside the in-app survey frame, so it carries no app chrome.
            case name === 'surveys/return':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('admin/'):
                return AdminLayout;
            case name.startsWith('settings/'):
                return [AccountShell, SettingsLayout];
            default:
                return UserLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: 'var(--color-brand)',
    },
});
