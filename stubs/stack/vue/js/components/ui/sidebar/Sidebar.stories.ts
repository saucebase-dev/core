import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import IconFolder from '~icons/lucide/folder';
import IconHome from '~icons/lucide/house';
import IconInbox from '~icons/lucide/inbox';
import IconSettings from '~icons/lucide/settings';
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
} from '.';

const components = {
    IconFolder,
    IconHome,
    IconInbox,
    IconSettings,
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
};

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
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        default: { control: false },
    },
    parameters: {
        // Each story has its own provider, but the open state is kept in a cookie.
        docs: { story: { inline: false, iframeHeight: '480px' } },
        layout: 'fullscreen',
    },
    args: { collapsible: 'icon' },
    render: (args) => ({
        components,
        setup: () => ({ args }),
        template: `
            <SidebarProvider>
                <Sidebar v-bind="args">
                    <SidebarHeader>
                        <span class="px-2 text-sm font-semibold group-data-[collapsible=icon]:hidden">Acme Inc.</span>
                    </SidebarHeader>
                    <SidebarContent>
                        <SidebarGroup>
                            <SidebarGroupLabel>Workspace</SidebarGroupLabel>
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    <SidebarMenuItem>
                                        <SidebarMenuButton is-active tooltip="Home"><IconHome /><span>Home</span></SidebarMenuButton>
                                    </SidebarMenuItem>
                                    <SidebarMenuItem>
                                        <SidebarMenuButton tooltip="Inbox"><IconInbox /><span>Inbox</span></SidebarMenuButton>
                                        <SidebarMenuBadge>12</SidebarMenuBadge>
                                    </SidebarMenuItem>
                                    <SidebarMenuItem>
                                        <SidebarMenuButton tooltip="Projects"><IconFolder /><span>Projects</span></SidebarMenuButton>
                                        <SidebarMenuSub>
                                            <SidebarMenuSubItem><SidebarMenuSubButton href="#">Website</SidebarMenuSubButton></SidebarMenuSubItem>
                                            <SidebarMenuSubItem><SidebarMenuSubButton href="#" is-active>Mobile app</SidebarMenuSubButton></SidebarMenuSubItem>
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
                                <SidebarMenuButton tooltip="Settings"><IconSettings /><span>Settings</span></SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarFooter>
                    <SidebarRail />
                </Sidebar>
                <SidebarInset>
                    <header class="flex h-14 items-center gap-2 border-b px-4">
                        <SidebarTrigger class="-ml-1" />
                        <span class="text-muted-foreground text-sm">Page</span>
                    </header>
                </SidebarInset>
            </SidebarProvider>`,
    }),
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
    render: (args) => ({
        components,
        setup: () => ({ args }),
        template: `
            <SidebarProvider>
                <Sidebar v-bind="args">
                    <SidebarContent>
                        <SidebarGroup>
                            <SidebarGroupLabel>Projects</SidebarGroupLabel>
                            <SidebarMenu>
                                <SidebarMenuItem v-for="n in 4" :key="n"><SidebarMenuSkeleton show-icon /></SidebarMenuItem>
                            </SidebarMenu>
                        </SidebarGroup>
                    </SidebarContent>
                </Sidebar>
            </SidebarProvider>`,
    }),
});
