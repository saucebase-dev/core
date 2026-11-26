import preview from '@/.storybook/preview';
import { Bell, Sparkles } from 'lucide-react';
import { expect } from 'storybook/test';
import AlertMessage from './AlertMessage';

/**
 * A one-line message about what just happened: a saved form, a failed request. Renders
 * nothing without a message, so a page can pass `status` or `error` straight in.
 *
 * `filled` for the outcome of the user's own action, `bordered` for a note inside a
 * section, `outlined` where a filled block would shout.
 */
const meta = preview.meta({
    title: 'App/AlertMessage',
    component: AlertMessage,
    args: { message: 'Your profile has been saved.', variant: 'success' },
    argTypes: {
        // A component can't go through the URL: pick one by name. Unset, the variant's own.
        icon: {
            options: ['Bell', 'Sparkles'],
            mapping: { Bell, Sparkles },
            control: 'select',
        },
    },
});
export default meta;

/** An action worked. */
export const Success = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('alert')).toHaveTextContent(
            'Your profile has been saved.',
        );
    },
});

/** Something the user should know, with nothing to fix. */
export const Info = meta.story({
    args: { variant: 'info', message: 'Invitations expire after 7 days.' },
});

/** Something may go wrong if the user carries on. */
export const Warning = meta.story({
    args: { variant: 'warning', message: 'Your trial ends in 3 days.' },
});

/** An action failed. The default variant. */
export const ErrorState = meta.story({
    name: 'Error',
    args: {
        variant: 'error',
        message: 'These credentials do not match our records.',
    },
});

/** A note inside a section, quieter than filled. */
export const Bordered = meta.story({
    args: { variant: 'info', appearance: 'bordered' },
});

/** Only an edge, where a filled block would shout. */
export const Outlined = meta.story({
    args: { variant: 'warning', appearance: 'outlined' },
});

/** Without its icon, where space is tight. */
export const WithoutIcon = meta.story({ args: { hideIcon: true } });

/** No message: renders nothing. */
export const Empty = meta.story({
    args: { message: '' },
    play: async ({ canvas }) => {
        await expect(canvas.queryByRole('alert')).toBeNull();
    },
});
