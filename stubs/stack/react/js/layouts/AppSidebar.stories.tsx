import { itemTypes, navigation, user } from '@/.storybook/page';
import { preview } from '@/.storybook/preview';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { expect } from 'storybook/test';
import AppSidebar from './AppSidebar';

// The real sidebar, fed the page props the server shares.
const meta = preview.meta({
    title: 'Layout/Sidebar',
    component: AppSidebar,
    parameters: { layout: 'fullscreen', inertia: { navigation, auth: { user } } },
    // In the app's frame (AppLayout): the page sits on its own surface beside the sidebar.
    // SidebarInset is the page's <main>, so the placeholder is a plain <div>.
    render: () => (
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset>
                <header className="flex h-14 items-center gap-2 border-b px-4">
                    <SidebarTrigger className="-ml-1" />
                    <span className="text-muted-foreground text-sm">Page</span>
                </header>
                <div className="flex-1 p-6">
                    <div className="bg-muted text-muted-foreground flex h-full min-h-64 items-center justify-center rounded-lg border-2 border-dashed text-sm">
                        Page content
                    </div>
                </div>
            </SidebarInset>
        </SidebarProvider>
    ),
});

// What core gives a signed-in user: no module items, no Admin link.
export const SignedIn = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('link', { name: /Welcome/ })).toHaveAttribute(
            'data-active',
            'true',
        );
        await expect(canvas.getByRole('link', { name: /Documentation/ })).toBeVisible();
        await expect(canvas.queryByRole('link', { name: /Admin/ })).toBeNull();
        await expect(canvas.getByTestId('user-menu-name')).toHaveTextContent('Ada');
    },
});

// The design system's reference: every item type, not what a real user sees.
export const AllItemTypes = meta.story({
    parameters: { inertia: { navigation: itemTypes, auth: { user } } },
    play: async ({ canvas }) => {
        await expect(canvas.getByText('Platform')).toBeVisible();
        await expect(canvas.getByText('3')).toBeVisible();
        await expect(canvas.getByRole('button', { name: /Projects/ })).toBeVisible();
        await expect(canvas.getByRole('link', { name: /Admin/ })).toHaveAttribute('href', '#');
    },
});

export const SignedOut = meta.story({
    parameters: { inertia: { navigation, auth: { user: null } } },
    play: async ({ canvas }) => {
        await expect(canvas.queryByTestId('user-menu-trigger')).toBeNull();
    },
});

// A link click is an Inertia visit; in a story it must not load a page into the frame.
// Nor may Inertia rewrite the frame's URL: pointed at `/`, a reload nests Storybook in itself.
let frameUrl = '';
export const ClickingALink = meta.story({
    // At Storybook's frame URL, as in the browser; the test runner's own page sits at `/`.
    beforeEach: () => {
        const original = window.location.href;
        window.history.replaceState(window.history.state, '', '/iframe.html?id=story');
        frameUrl = window.location.href;

        return () => window.history.replaceState(window.history.state, '', original);
    },
    play: async ({ canvas, userEvent }) => {
        await expect(window.location.href).toBe(frameUrl);

        await userEvent.click(canvas.getByRole('link', { name: /Welcome/ }));
        await new Promise((resolve) => setTimeout(resolve, 500));

        await expect(document.body.querySelector('iframe')).toBeNull();
        await expect(window.location.href).toBe(frameUrl);
    },
});
