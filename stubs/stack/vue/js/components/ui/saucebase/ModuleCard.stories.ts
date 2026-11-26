import preview from '@/.storybook/preview';
import { expect, fn } from 'storybook/test';
import { ModuleCard, modules } from '.';

const [custom, auth, , , , themes, , tenancy] = modules;

/**
 * One module on the welcome page's showcase: its icon in the module's colour, name, a
 * short description, and a badge (new, soon, added). Cards drop in one after another,
 * by `index`. A click selects the module, which opens Site/ModuleModal.
 *
 * A module without docs yet (`href: null`) is dimmed and dashed.
 */
const meta = preview.meta({
    title: 'Site/ModuleCard',
    tags: ['contributor'],
    component: ModuleCard,
    args: { module: auth, index: 0, onSelect: fn() },
    decorators: [() => ({ template: '<div class="w-56 p-6"><story /></div>' })],
});
export default meta;

/** An available module. Clicking it selects it. */
export const Available = meta.story({
    play: async ({ args, canvas, userEvent }) => {
        await userEvent.click(canvas.getByRole('button', { name: 'Auth' }));

        await expect(args.onSelect).toHaveBeenCalledWith(auth);
    },
});

/** A new module, with its badge. */
export const New = meta.story({ args: { module: themes } });

/** Coming soon: dimmed, dashed, no docs yet. */
export const ComingSoon = meta.story({ args: { module: tenancy } });

/** The card that invites the user to build their own module. */
export const YourModule = meta.story({ args: { module: custom } });
