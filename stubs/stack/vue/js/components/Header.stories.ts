import { user } from '@/.storybook/page';
import preview from '@/.storybook/preview';
import { expect, waitFor } from 'storybook/test';
import Header from './Header.vue';

const landing = [
    { title: 'Features', slug: 'features', url: '/#features' },
    { title: 'Pricing', slug: 'pricing', url: '/pricing', active: true },
    {
        title: 'Docs',
        slug: 'docs',
        url: 'https://saucebase-dev.github.io/docs/',
        external: true,
        newPage: true,
    },
];

/**
 * The public site's header: the logo, the landing links (the `navigation.landing` page
 * prop, which modules add to), language and theme, and sign-in or the dashboard link.
 *
 * Fixed to the top; it hides on scroll down and comes back on scroll up. Below `lg` the
 * links move into a menu.
 */
const meta = preview.meta({
    title: 'Layout/Header',
    component: Header,
    parameters: {
        // Its stories differ only in page props, which one docs page shares.
        docs: { story: { inline: false, iframeHeight: '480px' } },
        layout: 'fullscreen',
        inertia: { navigation: { landing } },
    },
    // A page under it, tall enough to scroll, so hiding on scroll can be seen; the frame fits
    // the open mobile menu.
    decorators: [
        () => ({
            template: `
                <story />
                <main class="mx-auto min-h-[250vh] max-w-7xl space-y-6 px-6 pt-24 pb-12">
                    <div class="bg-muted h-48 rounded-xl" />
                    <div class="grid gap-6 sm:grid-cols-3">
                        <div v-for="n in 3" :key="n" class="bg-muted h-40 rounded-xl" />
                    </div>
                    <div class="bg-muted h-96 rounded-xl" />
                </main>`,
        }),
    ],
});
export default meta;

/** A visitor: sign in, and get started when registration is open. */
export const SignedOut = meta.story({
    parameters: {
        inertia: { auth: { user: null, registration_enabled: true } },
    },
    play: async ({ canvas }) => {
        await expect(canvas.getByTestId('header-sign-in')).toBeVisible();
        await expect(canvas.getByTestId('header-get-started')).toBeVisible();
        await expect(
            canvas.getByRole('link', { name: 'Pricing' }),
        ).toBeVisible();
    },
});

/** A visitor while registration is closed: sign in only. */
export const RegistrationClosed = meta.story({
    parameters: {
        inertia: { auth: { user: null, registration_enabled: false } },
    },
    play: async ({ canvas }) => {
        await expect(canvas.queryByTestId('header-get-started')).toBeNull();
    },
});

/** A signed-in user: the dashboard link and log out. */
export const SignedIn = meta.story({
    parameters: { inertia: { auth: { user } } },
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('link', { name: 'Dashboard' }),
        ).toBeVisible();
        await expect(canvas.queryByTestId('header-sign-in')).toBeNull();
    },
});

/** Scrolling down hides it; scrolling back up brings it back. */
export const Scrolling = meta.story({
    parameters: {
        inertia: { auth: { user: null, registration_enabled: true } },
    },
    play: async ({ canvas }) => {
        const header = canvas.getByRole('banner');

        window.scrollTo({ top: 400, behavior: 'instant' });
        await waitFor(() => expect(header).toHaveClass('-translate-y-full'));

        window.scrollTo({ top: 300, behavior: 'instant' });
        await waitFor(() => expect(header).toHaveClass('translate-y-0'));

        window.scrollTo({ top: 0, behavior: 'instant' });
    },
});

/** On a phone: the links fold into a menu. No play function: story tests run at desktop width. */
export const Mobile = meta.story({
    parameters: {
        inertia: { auth: { user: null, registration_enabled: true } },
    },
    globals: { viewport: { value: 'mobile1' } },
});
