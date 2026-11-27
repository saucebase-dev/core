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
import type { ComponentProps } from 'react';
import type { Navigation } from '@saucebase/core/types';
import { usePage } from '@inertiajs/react';
import { hasGlobalComponent } from '@saucebase/react/lib/globalComponents';

// Sidebar's props, with the app's defaults: an inset that collapses to icons.
export default function AppSidebar({
    variant = 'inset',
    collapsible = 'icon',
    className = 'bg-transparent',
    ...props
}: ComponentProps<typeof Sidebar>) {
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
        <Sidebar
            variant={variant}
            collapsible={collapsible}
            className={className}
            {...props}
        >
            <SidebarHeader data-testid="sidebar-header">
                {brandIsClaimed ? (
                    <GlobalComponents position="dashboard::sidebar.brand" />
                ) : (
                    <AppBrand />
                )}
            </SidebarHeader>
            <SidebarContent>
                <GlobalComponents position="dashboard::sidebar.start" />
                <NavGroup items={items} />
                {/* Between the nav and the bottom links: a module's own list (chat history). */}
                <GlobalComponents position="dashboard::sidebar.content" />
                <NavGroup items={secondaryItems} className="mt-auto" />
                <GlobalComponents position="dashboard::sidebar.end" />
            </SidebarContent>
            <SidebarFooter>
                {user && <NavUser user={user} items={userItems} />}
            </SidebarFooter>
        </Sidebar>
    );
}
