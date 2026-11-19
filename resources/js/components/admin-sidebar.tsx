import { Link } from '@inertiajs/react';
import {
    Activity,
    Award,
    Bot,
    Boxes,
    ChartColumn,
    Clapperboard,
    LayoutGrid,
    ListChecks,
    PlugZap,
    Route,
    Scale,
    Settings2,
    SlidersHorizontal,
    Shield,
    ShieldCheck,
    Users,
    WalletCards,
    Webhook,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

const overviewItems: NavItem[] = [
    {
        title: 'Overview',
        href: '/admin',
        icon: LayoutGrid,
    },
];

const peopleItems: NavItem[] = [
    {
        title: 'Administrators',
        href: '/admin/administrators',
        icon: Shield,
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: Users,
    },
];

const contentItems: NavItem[] = [
    {
        title: 'Moderation',
        href: '/admin/quotes',
        icon: ShieldCheck,
    },
];

const advertisingItems: NavItem[] = [
    {
        title: 'Overview',
        href: '/admin/ads',
        icon: Clapperboard,
    },
    {
        title: 'Survey activity',
        href: '/admin/ads/activity',
        icon: ListChecks,
    },
    {
        title: 'Providers',
        href: '/admin/ads/providers',
        icon: PlugZap,
    },
    {
        title: 'Rewards',
        href: '/admin/ads/rewards',
        icon: Award,
    },
    {
        title: 'Provider requests',
        href: '/admin/ads/requests',
        icon: Activity,
    },
    {
        title: 'Callbacks',
        href: '/admin/ads/callbacks',
        icon: Webhook,
    },
    {
        title: 'Reconciliation',
        href: '/admin/ads/reconciliation',
        icon: Scale,
    },
];

// These are parents of other entries, so they are only "active" on their own page.
const exactMatchHrefs = ['/admin', '/admin/ads'];

const operationsItems: NavItem[] = [
    {
        title: 'Revenue',
        href: '/admin/revenue',
        icon: ChartColumn,
    },
    {
        title: 'Withdrawals',
        href: '/admin/withdrawals',
        icon: WalletCards,
    },
];

const aiItems: NavItem[] = [
    {
        title: 'Providers',
        href: '/admin/ai/providers',
        icon: Bot,
    },
    {
        title: 'Models',
        href: '/admin/ai/models',
        icon: Boxes,
    },
    {
        title: 'Routing',
        href: '/admin/ai/routing',
        icon: Route,
    },
    // Test chat is hidden for now. The page and routes still exist at /admin/ai/playground.
    // {
    //     title: 'Test chat',
    //     href: '/admin/ai/playground',
    //     icon: MessageSquare,
    // },
    {
        title: 'Usage',
        href: '/admin/ai/usage',
        icon: ChartColumn,
    },
    {
        title: 'AI settings',
        href: '/admin/ai/settings',
        icon: Settings2,
    },
];

const systemItems: NavItem[] = [
    {
        title: 'Application settings',
        href: '/admin/settings',
        icon: SlidersHorizontal,
    },
];

export function AdminSidebar() {
    return (
        <Sidebar
            collapsible="icon"
            variant="sidebar"
            className="text-slate-ink [--color-sidebar-accent-foreground:var(--color-brand-deep)] [--color-sidebar-accent:var(--color-brand-soft)] [--color-sidebar-border:var(--color-hairline)] [--color-sidebar-foreground:var(--color-slate-ink)] [--color-sidebar:var(--color-paper)] [&_[data-sidebar=sidebar]]:text-slate-ink"
        >
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <AdminNavGroup label="Platform" items={overviewItems} />
                <AdminNavGroup label="People" items={peopleItems} />
                <AdminNavGroup label="Content" items={contentItems} />
                <AdminNavGroup label="Advertising" items={advertisingItems} />
                <AdminNavGroup label="Operations" items={operationsItems} />
                <AdminNavGroup label="AI Management" items={aiItems} />
                <AdminNavGroup label="System" items={systemItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

function AdminNavGroup({ label, items }: { label: string; items: NavItem[] }) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{label}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const active =
                        typeof item.href === 'string' &&
                        exactMatchHrefs.includes(item.href)
                            ? isCurrentUrl(item.href)
                            : isCurrentOrParentUrl(item.href);

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={active}
                                tooltip={{ children: item.title }}
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
