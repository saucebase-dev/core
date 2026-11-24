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
    parameters: { layout: 'fullscreen' },
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
        docs: { story: { inline: false, iframeHeight: '320px' } },
        inertia: {
            settings: {
                sections: [],
                general: { site_name: 'Northwind Analytics' },
            },
        },
    },
});
