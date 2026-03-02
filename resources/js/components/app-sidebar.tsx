import { usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { ClipboardList, LayoutGrid, MapPin, Settings2, Trophy, Users, Video } from 'lucide-react';
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
import ranking from '@/routes/admin/ranking';
import type { NavItem } from '@/types';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage().props;

    const footerNavItems: NavItem[] = [
        ...(auth.roles.includes('administrator')
            ? [
                  {
                      title: 'Users Management',
                      href: usersIndex().url,
                      icon: Users,
                  },
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
        {
            title: 'Courts',
            href: courtsIndex().url,
            icon: MapPin,
        },
        {
            title: 'Games',
            href: gamesIndex().url,
            icon: Video,
        },
        {
            title: 'Moderation Queues',
            href: moderation.index().url,
            icon: ClipboardList,
        },
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
