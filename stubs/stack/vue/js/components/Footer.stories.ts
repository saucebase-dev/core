import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import Footer from './Footer.vue';

/**
 * The public site's footer: the year and site name, the project's links, the legal
 * pages, and the site name as a large watermark sized to fit one line.
 *
 * The name comes from the brand settings (`settings.general.site_name`).
 */
const meta = preview.meta({
    title: 'Layout/Footer',
    component: Footer,
    parameters: {
        // In its own frame: the watermark is sized from the window's width, which inline
        // would be the whole docs page's.
        docs: { story: { inline: false, iframeHeight: '400px' } },
        layout: 'fullscreen',
    },
});
export default meta;

/** With the default site name. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByTestId('footer-app-name')).toHaveTextContent(
            'Saucebase',
        );
        await expect(
            canvas.getByRole('link', { name: 'Privacy' }),
        ).toBeVisible();
    },
});

/** A long name: the watermark shrinks to stay on one line. */
export const LongName = meta.story({
    parameters: {
        inertia: {
            settings: {
                sections: [],
                general: { site_name: 'Northwind Analytics' },
            },
        },
    },
});

/** A plain link, such as GitHub's, stays in the story: it goes to the Actions panel. */
export const ClickingALink = meta.story({
    play: async ({ canvas, userEvent }) => {
        // Heard last, after Storybook's own handler: whether the click would leave. Then
        // cancelled either way, so a failing test doesn't take the test runner away.
        let leaves = true;
        const listen = (event: Event) => {
            leaves = !event.defaultPrevented;
            event.preventDefault();
        };
        window.addEventListener('click', listen);
        await userEvent.click(canvas.getByRole('link', { name: 'GitHub' }));
        window.removeEventListener('click', listen);

        await expect(leaves).toBe(false);
    },
});
