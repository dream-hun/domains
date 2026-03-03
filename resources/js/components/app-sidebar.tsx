import { usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import {
    ClipboardList,
    LayoutGrid,
    MapPin,
    Settings2,
    ShieldAlert,
    Trophy,
    Users,
    Video,
} from 'lucide-react';
import { index as courtsIndex } from '@/actions/App/Http/Controllers/Admin/CourtController';
import { index as gamesIndex } from '@/actions/App/Http/Controllers/Admin/GameController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, leaderboard } from '@/routes';
import moderation from '@/routes/admin/moderation';
import override from '@/routes/admin/override';
import ranking from '@/routes/admin/ranking';
import type { NavItem } from '@/types';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage().props;

    const can = (permission: string) => auth.permissions.includes(permission);

    const footerNavItems: NavItem[] = [
        ...(can('manage-users')
            ? [
                  {
                      title: 'Users Management',
                      href: usersIndex().url,
                      icon: Users,
                  },
              ]
            : []),
        ...(can('manage-ranking-configuration')
            ? [
                  {
                      title: 'Ranking Config',
                      href: ranking.edit().url,
                      icon: Settings2,
                  },
              ]
            : []),
    ];

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Leaderboard',
            href: leaderboard().url,
            icon: Trophy,
        },
        ...(can('edit-courts')
            ? [
                  {
                      title: 'Courts',
                      href: courtsIndex().url,
                      icon: MapPin,
                  },
              ]
            : []),
        ...(can('view-games')
            ? [
                  {
                      title: 'Games',
                      href: gamesIndex().url,
                      icon: Video,
                  },
              ]
            : []),
        ...(can('moderate-games')
            ? [
                  {
                      title: 'Moderation Queues',
                      href: moderation.index().url,
                      icon: ClipboardList,
                  },
              ]
            : []),
        ...(can('override-moderation')
            ? [
                  {
                      title: 'Flagged Games',
                      href: override.index().url,
                      icon: ShieldAlert,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
