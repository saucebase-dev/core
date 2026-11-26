import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import SiteLayout from './SiteLayout.vue';

/**
 * The public site's frame: the header, the page and the footer, plus the page's title
 * and its search and social-share tags. Modules add to it through the `site::*`
 * positions.
 *
 * Without a `description`, the site's own (`settings.general.site_description`) is used.
 */
const meta = preview.meta({
    title: 'Layout/SiteLayout',
    component: SiteLayout,
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        default: { control: false },
    },
    parameters: {
        docs: { story: { inline: false, iframeHeight: '640px' } },
        layout: 'fullscreen',
        inertia: { auth: { user: null, registration_enabled: true } },
    },
    args: { title: 'Pricing' },
    render: (args) => ({
        components: { SiteLayout },
        setup: () => ({ args }),
        template: `
            <SiteLayout v-bind="args">
                <main class="mx-auto w-full max-w-7xl flex-1 px-6 pt-28 pb-16">
                    <div class="bg-muted text-muted-foreground flex min-h-64 items-center justify-center rounded-lg border-2 border-dashed text-sm">Page content</div>
                </main>
            </SiteLayout>`,
    }),
});
export default meta;

/** A public page between the header and the footer. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByText('Page content')).toBeVisible();
        await expect(canvas.getByTestId('footer-app-name')).toBeVisible();
    },
});
