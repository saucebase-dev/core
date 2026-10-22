import AppBrand from '@/components/AppBrand';
import { GlobalComponents } from '@/components/GlobalComponents';
import NavGroup from '@/components/ui/saucebase/navigation/NavGroup';
import NavUser from '@/components/ui/saucebase/navigation/NavUser';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import type { PageProps, User } from '@/types';
import type { Navigation } from '@saucebase/core/types';
import { usePage } from '@inertiajs/react';
import {
    getGlobalComponents,
    hasGlobalComponent,
} from '@saucebase/react/lib/globalComponents';

export default function AppSidebar() {
    const page =
        usePage<PageProps<{ navigation: Navigation; auth: { user: User } }>>();

    const items = page.props.navigation?.main ?? [];
    const secondaryItems = page.props.navigation?.secondary ?? [];
    const userItems = page.props.navigation?.user ?? [];
    const user = page.props.auth?.user;

    // A module may own this block instead. Registration happens at import time, so this
    // is settled before the first render.
    const brandIsClaimed = hasGlobalComponent('dashboard::sidebar.brand');

    return (
        <Sidebar variant="inset" collapsible="icon" className="bg-transparent">
            <SidebarHeader data-testid="sidebar-header">
                {brandIsClaimed ? (
                    getGlobalComponents('dashboard::sidebar.brand').map((C, i) => (
                        <C key={i} />
                    ))
                ) : (
                    <AppBrand />
                )}
            </SidebarHeader>
            <SidebarContent>
                <GlobalComponents position="dashboard::sidebar.start" />
                <NavGroup items={items} />
                <NavGroup items={secondaryItems} className="mt-auto" />
                <GlobalComponents position="dashboard::sidebar.end" />
            </SidebarContent>
            <SidebarFooter>
                {user && <NavUser user={user} items={userItems} />}
            </SidebarFooter>
        </Sidebar>
    );
}
