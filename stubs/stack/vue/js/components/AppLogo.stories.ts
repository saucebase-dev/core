import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import AppLogo from './AppLogo.vue';

/**
 * The brand as artwork, from the brand settings: an upload, or Saucebase's own. Both the
 * light and dark artwork render and CSS shows the one for the theme, so nothing flashes
 * on load.
 *
 * `logo` (the wide lockup, name included) wherever it fits; `icon` (the square mark)
 * where a name can't be read: a collapsed sidebar, a list row.
 */
const meta = preview.meta({
    title: 'App/AppLogo',
    component: AppLogo,
});
export default meta;

/** The wide lockup, at its default size. */
export const Logo = meta.story({
    play: async ({ canvas }) => {
        // The other theme's artwork is hidden, so only one counts as an image.
        await expect(
            canvas.getByRole('img', { name: 'Saucebase' }),
        ).toBeVisible();
    },
});

/** The square mark. */
export const Icon = meta.story({ args: { variant: 'icon' } });

/** `sm` in the sidebar, `md` in headers, larger on a hero. */
export const Large = meta.story({ args: { size: 'xl' } });
