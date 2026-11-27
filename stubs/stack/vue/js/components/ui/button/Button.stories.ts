import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import { Button } from '.';

/**
 * Starts an action: submits a form, opens a dialog, saves. To go to another page, use a
 * link; with `as-child` a Button can wrap one and keep the link's behaviour.
 *
 * One `default` button per view, for its main action; the rest `secondary`, `outline` or
 * `ghost`.
 */
const meta = preview.meta({
    title: 'UI/Button',
    component: Button,
    // The default slot: the button's label.
    args: { default: 'Save' },
});
export default meta;

/** The view's main action. One per view. */
export const Primary = meta.story({
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('button', { name: 'Save' }),
        ).toBeVisible();
    },
});

/** An action beside the main one, such as Cancel. */
export const Secondary = meta.story({ args: { variant: 'secondary' } });

/** A secondary action that needs an edge, on a busy or tinted surface. */
export const Outline = meta.story({ args: { variant: 'outline' } });

/** Deletes, or can't be undone. Confirm before it acts. */
export const Destructive = meta.story({ args: { variant: 'destructive' } });

/** The quietest: toolbars, table rows, icon buttons. */
export const Ghost = meta.story({ args: { variant: 'ghost' } });

/** An action that reads as text, inside a sentence. */
export const Link = meta.story({ args: { variant: 'link' } });
