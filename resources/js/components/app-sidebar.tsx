import { usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { LayoutGrid, MapPin, Users, Video } from 'lucide-react';
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
import { dashboard } from '@/routes';
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
            title: 'Courts',
            href: courtsIndex().url,
            icon: MapPin,
        },
        {
            title: 'Games',
            href: gamesIndex().url,
            icon: Video,
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
