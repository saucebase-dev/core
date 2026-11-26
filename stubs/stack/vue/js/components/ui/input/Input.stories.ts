import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import { Input } from '.';

/**
 * A bare one-line text input. In forms use UI/InputField, which adds the label, the
 * error and the password toggle; use Input alone inside your own layout (a search box,
 * UI/InputGroup), labelled with `aria-label` or a `Label`.
 */
const meta = preview.meta({
    title: 'UI/Input',
    component: Input,
    parameters: {
        // Native attributes, not props: bound beside the args.
        attrs: {
            'aria-label': 'Email',
            placeholder: 'you@example.com',
            type: 'email',
        },
    },
    render: (args, { parameters }) => ({
        components: { Input },
        setup: () => ({ args, attrs: parameters.attrs }),
        template:
            '<div class="max-w-sm"><Input v-bind="{ ...attrs, ...args }" /></div>',
    }),
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
    parameters: { attrs: { disabled: true } },
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('textbox')).toBeDisabled();
    },
});

/** Rejected by the server: a red edge. */
export const Invalid = meta.story({
    parameters: { attrs: { 'aria-invalid': true } },
});

/** Picks a file: an avatar, a CSV to import. */
export const File = meta.story({
    parameters: { attrs: { type: 'file', 'aria-label': 'Avatar' } },
});
