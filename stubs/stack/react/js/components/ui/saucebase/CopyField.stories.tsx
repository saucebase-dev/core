import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import { CopyField } from '.';

/**
 * A read-only value with a copy button: an API token, an invite link, a webhook secret.
 * Focusing the field selects the value; the button copies it, shows a tick for two
 * seconds, and confirms with a toast.
 */
const meta = preview.meta({
    title: 'UI/CopyField',
    component: CopyField,
    args: {
        value: 'https://acme.example.com/invite/8f3a2c91',
        testId: 'invite-link',
    },
});
export default meta;

/** An invite link, ready to copy. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByTestId('invite-link')).toHaveValue(
            'https://acme.example.com/invite/8f3a2c91',
        );
        await expect(canvas.getByRole('textbox')).toHaveAttribute('readonly');
        await expect(
            canvas.getByRole('button', { name: 'Copy' }),
        ).toBeVisible();
    },
});

/** A secret longer than the field: it scrolls inside the input. */
export const LongValue = meta.story({
    args: {
        value: 'sb_live_4eC39HqLyjWDarjtT1zdp7dc4eC39HqLyjWDarjtT1zdp7dc',
        testId: 'api-token',
        className: 'max-w-sm',
    },
});
