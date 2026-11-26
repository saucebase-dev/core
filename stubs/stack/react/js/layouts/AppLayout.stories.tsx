import { navigation, user } from '@/.storybook/page';
import preview from '@/.storybook/preview';
import { expect, waitFor } from 'storybook/test';
import AppLayout from './AppLayout';

/**
 * The signed-in app's frame: the sidebar, a header with the sidebar trigger and the
 * breadcrumbs, and the page. Modules add to it through the `dashboard::*` positions.
 *
 * Breadcrumbs come from the server (the `breadcrumbs` page prop) unless the page passes
 * its own; without either, the `title` stands in.
 */
const meta = preview.meta({
    title: 'Layout/AppLayout',
    component: AppLayout,
    parameters: {
        // Its stories differ only in page props, which one docs page shares.
        docs: { story: { inline: false, iframeHeight: '480px' } },
        layout: 'fullscreen',
        inertia: { navigation, auth: { user } },
    },
    args: {
        title: 'Dashboard',
        children: (
            <div className="p-6">
                <div className="bg-muted text-muted-foreground flex min-h-64 items-center justify-center rounded-lg border-2 border-dashed text-sm">
                    Page content
                </div>
            </div>
        ),
    },
});
export default meta;

/** A page with only a title: it shows as the one breadcrumb. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByText('Dashboard')).toBeVisible();
        await waitFor(() =>
            expect(canvas.getByText('Page content')).toBeVisible(),
        );
    },
});

/** A nested page: the trail back up, as links. */
export const WithBreadcrumbs = meta.story({
    args: {
        breadcrumbs: [
            { title: 'Settings', url: '/settings' },
            { title: 'Profile' },
        ],
    },
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('link', { name: 'Settings' }),
        ).toBeVisible();
        await expect(canvas.getByText('Profile')).toBeVisible();
    },
});
