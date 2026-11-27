import preview from '@/.storybook/preview';
import { expect, fn, screen, waitFor } from 'storybook/test';
import { ModuleModal, modules } from '.';

const [custom, auth, , , , , , tenancy] = modules;

/**
 * A module's details over the welcome page: description, features, the command that
 * installs it, and its docs. Opens when a Site/ModuleCard is selected; the backdrop and
 * the close button close it.
 *
 * Marked Installed when the app has the module (the `modules` page prop).
 */
const meta = preview.meta({
    title: 'Site/ModuleModal',
    tags: ['contributor'],
    component: ModuleModal,
    parameters: {
        // It covers the page, so each story gets its own frame.
        docs: { story: { inline: false, iframeHeight: '640px' } },
        layout: 'fullscreen',
    },
    args: { selectedMod: auth, onClose: fn() },
});
export default meta;

/** A module to install: its command and docs. The close button closes it. */
export const Default = meta.story({
    play: async ({ args, userEvent }) => {
        await expect(
            await screen.findByTestId('module-install-command'),
        ).toHaveTextContent('php artisan saucebase:module-install auth');
        // It fades in.
        await waitFor(() =>
            expect(screen.getByRole('button', { name: 'Copy' })).toBeVisible(),
        );

        await userEvent.click(screen.getByRole('button', { name: 'Close' }));

        await expect(args.onClose).toHaveBeenCalled();
    },
});

/** A module the app already has. */
export const Installed = meta.story({
    parameters: { inertia: { modules: { auth: 'Auth' } } },
    play: async () => {
        // It fades in.
        await waitFor(() =>
            expect(screen.getByText('Installed')).toBeVisible(),
        );
    },
});

/** Coming soon: no command, no docs yet. */
export const ComingSoon = meta.story({
    args: { selectedMod: tenancy },
    play: async () => {
        await waitFor(() =>
            expect(screen.getByText('Coming Soon')).toBeVisible(),
        );
        await expect(screen.queryByTestId('module-install-command')).toBeNull();
    },
});

/** Your own module: the command that scaffolds one. */
export const YourModule = meta.story({ args: { selectedMod: custom } });
