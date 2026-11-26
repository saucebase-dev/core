import preview from '@/.storybook/preview';
import { Folder, House, Inbox, Settings } from 'lucide-react';
import { expect } from 'storybook/test';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSkeleton,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    SidebarProvider,
    SidebarRail,
    SidebarSeparator,
    SidebarTrigger,
} from './sidebar';

/**
 * The parts a sidebar is built from: a provider that holds its open state (and the
 * Ctrl/Cmd+B shortcut), the sidebar with header, groups and footer, menus with badges
 * and submenus, and the trigger that collapses it.
 *
 * The app's own sidebar is Layout/Sidebar; compose these for a second one, such as a
 * settings section's.
 */
const meta = preview.meta({
    title: 'UI/Sidebar',
    component: Sidebar,
    parameters: {
        // Each story has its own provider, but the open state is kept in a cookie.
        docs: { story: { inline: false, iframeHeight: '480px' } },
        layout: 'fullscreen',
    },
    args: { collapsible: 'icon' },
    render: (args) => (
        <SidebarProvider>
            <Sidebar {...args}>
                <SidebarHeader>
                    <span className="px-2 text-sm font-semibold group-data-[collapsible=icon]:hidden">
                        Acme Inc.
                    </span>
                </SidebarHeader>
                <SidebarContent>
                    <SidebarGroup>
                        <SidebarGroupLabel>Workspace</SidebarGroupLabel>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                <SidebarMenuItem>
                                    <SidebarMenuButton isActive tooltip="Home">
                                        <House />
                                        <span>Home</span>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                                <SidebarMenuItem>
                                    <SidebarMenuButton tooltip="Inbox">
                                        <Inbox />
                                        <span>Inbox</span>
                                    </SidebarMenuButton>
                                    <SidebarMenuBadge>12</SidebarMenuBadge>
                                </SidebarMenuItem>
                                <SidebarMenuItem>
                                    <SidebarMenuButton tooltip="Projects">
                                        <Folder />
                                        <span>Projects</span>
                                    </SidebarMenuButton>
                                    <SidebarMenuSub>
                                        <SidebarMenuSubItem>
                                            <SidebarMenuSubButton href="#">
                                                Website
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                        <SidebarMenuSubItem>
                                            <SidebarMenuSubButton
                                                href="#"
                                                isActive
                                            >
                                                Mobile app
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    </SidebarMenuSub>
                                </SidebarMenuItem>
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                </SidebarContent>
                <SidebarSeparator />
                <SidebarFooter>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton tooltip="Settings">
                                <Settings />
                                <span>Settings</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarFooter>
                <SidebarRail />
            </Sidebar>
            <SidebarInset>
                <header className="flex h-14 items-center gap-2 border-b px-4">
                    <SidebarTrigger className="-ml-1" />
                    <span className="text-muted-foreground text-sm">Page</span>
                </header>
            </SidebarInset>
        </SidebarProvider>
    ),
});
export default meta;

/** Groups, an active item, a badge and a submenu. The trigger collapses it to icons and back. */
export const Default = meta.story({
    play: async ({ canvas, canvasElement, userEvent }) => {
        const sidebar = canvasElement.querySelector('[data-slot="sidebar"]');
        // The rail on the sidebar's edge has the same name.
        const [trigger] = canvas
            .getAllByRole('button', { name: 'Toggle Sidebar' })
            .filter((el) => el.dataset.sidebar === 'trigger');
        await expect(canvas.getByText('12')).toBeVisible();

        await userEvent.click(trigger!);
        await expect(sidebar).toHaveAttribute('data-state', 'collapsed');

        await userEvent.click(trigger!);
        await expect(sidebar).toHaveAttribute('data-state', 'expanded');
    },
});

/** Floating: inset from the edges, with its own border and shadow. */
export const Floating = meta.story({ args: { variant: 'floating' } });

/** Menu items still loading. */
export const Loading = meta.story({
    render: (args) => (
        <SidebarProvider>
            <Sidebar {...args}>
                <SidebarContent>
                    <SidebarGroup>
                        <SidebarGroupLabel>Projects</SidebarGroupLabel>
                        <SidebarMenu>
                            {[1, 2, 3, 4].map((n) => (
                                <SidebarMenuItem key={n}>
                                    <SidebarMenuSkeleton showIcon />
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                </SidebarContent>
            </Sidebar>
        </SidebarProvider>
    ),
});
