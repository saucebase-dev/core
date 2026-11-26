import { itemTypes, navigation, user } from '@/.storybook/page';
import preview from '@/.storybook/preview';
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { expect } from 'storybook/test';
import AppSidebar from './AppSidebar';

/**
 * The signed-in app's main navigation, beside every page. Its items come from the server
 * (the `navigation` page prop): core gives Welcome and the help links, modules add
 * theirs.
 *
 * Collapses to icons on desktop and to a sheet on mobile; the trigger toggles it.
 */
const meta = preview.meta({
    title: 'Layout/Sidebar',
    component: AppSidebar,
    parameters: {
        docs: {
            // Its stories differ only in page props, which one docs page shares.
            story: { inline: false, iframeHeight: '480px' },
        },
        layout: 'fullscreen',
        inertia: { navigation, auth: { user } },
    },
    // In the app's frame (AppLayout): the page sits on its own surface beside the sidebar.
    // SidebarInset is the page's <main>, so the placeholder is a plain <div>.
    render: (args) => (
        <SidebarProvider>
            <AppSidebar {...args} />
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
export default meta;

/** What core gives a signed-in user: no module items, no Admin link. */
export const SignedIn = meta.story({
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('link', { name: /Welcome/ }),
        ).toHaveAttribute('data-active', 'true');
        await expect(
            canvas.getByRole('link', { name: /Documentation/ }),
        ).toBeVisible();
        await expect(canvas.queryByRole('link', { name: /Admin/ })).toBeNull();
        await expect(canvas.getByTestId('user-menu-name')).toHaveTextContent(
            'Ada',
        );
    },
});

/** Every item type a module can add: labels, badges, children, external links. Not what a real user sees. */
export const AllItemTypes = meta.story({
    parameters: { inertia: { navigation: itemTypes, auth: { user } } },
    play: async ({ canvas }) => {
        await expect(canvas.getByText('Platform')).toBeVisible();
        await expect(canvas.getByText('3')).toBeVisible();
        await expect(
            canvas.getByRole('button', { name: /Projects/ }),
        ).toBeVisible();
        await expect(
            canvas.getByRole('link', { name: /Admin/ }),
        ).toHaveAttribute('href', '#');
    },
});

/** A guest: no user menu. */
export const SignedOut = meta.story({
    parameters: { inertia: { navigation, auth: { user: null } } },
    play: async ({ canvas }) => {
        await expect(canvas.queryByTestId('user-menu-trigger')).toBeNull();
    },
});

// A link click is an Inertia visit; in a story it must not load a page into the frame.
// Nor may Inertia rewrite the frame's URL: pointed at `/`, a reload nests Storybook in itself.
let frameUrl = '';
/** A click is an Inertia visit. Here it goes to the Actions panel instead of loading a page. */
export const ClickingALink = meta.story({
    // At Storybook's frame URL, as in the browser; the test runner's own page sits at `/`.
    beforeEach: () => {
        const original = window.location.href;
        window.history.replaceState(
            window.history.state,
            '',
            '/iframe.html?id=story',
        );
        frameUrl = window.location.href;

        return () =>
            window.history.replaceState(window.history.state, '', original);
    },
    play: async ({ canvas, userEvent }) => {
        await expect(window.location.href).toBe(frameUrl);

        await userEvent.click(canvas.getByRole('link', { name: /Welcome/ }));
        await new Promise((resolve) => setTimeout(resolve, 500));

        await expect(document.body.querySelector('iframe')).toBeNull();
        await expect(window.location.href).toBe(frameUrl);
    },
});

/** The user menu holds the app's own theme selector. It keeps Storybook's theme. */
export const OpeningTheUserMenu = meta.story({
    // Dark, so the app's fallback (the device's theme, light in tests) would show.
    globals: { theme: 'dark' },
    beforeEach: () => localStorage.removeItem('appearance'),
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(canvas.getByTestId('user-menu-trigger'));

        await expect(document.documentElement).toHaveClass('dark');
    },
});

/** On the right, for right-to-left languages or a layout that wants it there. */
export const OnTheRight = meta.story({
    args: { side: 'right' },
    play: async ({ canvasElement }) => {
        await expect(
            canvasElement.querySelector('[data-slot="sidebar"]'),
        ).toHaveAttribute('data-side', 'right');
    },
});
