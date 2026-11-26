import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import { Input } from './input';

/**
 * A bare one-line text input. Use it inside your own layout (a search box,
 * UI/InputGroup), labelled with `aria-label` or a `Label`; in forms, wrap it in a
 * `Field` (UI/Field) for the label and the error.
 */
const meta = preview.meta({
    title: 'UI/Input',
    component: Input,
    args: {
        'aria-label': 'Email',
        placeholder: 'you@example.com',
        type: 'email',
    },
    decorators: [
        (Story) => (
            <div className="max-w-sm">
                <Story />
            </div>
        ),
    ],
});
export default meta;

/** Empty, with a placeholder. Typing fills it. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        const input = canvas.getByRole('textbox', { name: 'Email' });

        await userEvent.type(input, 'ada@example.com');

        await expect(input).toHaveValue('ada@example.com');
    },
});

/** With a value already in it. */
export const Filled = meta.story({ args: { defaultValue: 'ada@example.com' } });

/** Can't be edited. */
export const Disabled = meta.story({
    args: { disabled: true },
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('textbox')).toBeDisabled();
    },
});

/** Rejected by the server: a red edge. */
export const Invalid = meta.story({ args: { 'aria-invalid': true } });

/** Picks a file: an avatar, a CSV to import. */
export const File = meta.story({
    args: { type: 'file', 'aria-label': 'Avatar' },
});
