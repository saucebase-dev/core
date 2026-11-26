import preview from '@/.storybook/preview';
import { expect, screen, waitFor } from 'storybook/test';
import ThemeSelector from './ThemeSelector.vue';

/**
 * Lets the user pick light, dark, or the device's theme. The choice is kept in the
 * browser and in a cookie, so the server renders the right theme next time.
 *
 * A dropdown in the site header, inline buttons on a settings page. Inside another menu
 * (the user menu), `mode="submenu"`: see Layout/Sidebar.
 */
const meta = preview.meta({
    title: 'App/ThemeSelector',
    component: ThemeSelector,
    // No reveal animation: the View Transitions API would hold the play function up.
    args: { disableAnimation: true },
});
export default meta;

/** A dropdown, as in the site header. */
export const Dropdown = meta.story({
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(
            canvas.getByRole('button', { name: 'Toggle theme' }),
        );

        const dark = await screen.findByRole('menuitem', { name: 'Dark' });

        // The menu fades in.
        await waitFor(() => expect(dark).toBeVisible());
    },
});

/** Buttons side by side, as on a settings page. Picking one changes the theme. */
export const Inline = meta.story({
    args: { inline: true },
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(canvas.getByRole('button', { name: 'Dark' }));

        await expect(document.documentElement).toHaveClass('dark');

        await userEvent.click(canvas.getByRole('button', { name: 'Light' }));
    },
});

/** Inline, filling its container, without the device option. */
export const InlineFullWidth = meta.story({
    args: { inline: true, fullWidth: true, hideDevice: true },
    play: async ({ canvas }) => {
        await expect(
            canvas.queryByRole('button', { name: 'Device' }),
        ).toBeNull();
    },
});
