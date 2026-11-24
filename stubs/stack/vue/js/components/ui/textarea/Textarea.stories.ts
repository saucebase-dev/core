import preview from '@/.storybook/preview';
import { Label } from '@/components/ui/label';
import { expect } from 'storybook/test';
import { Textarea } from '.';

/**
 * Text that runs to more than one line: a message, a bio, feedback. It grows with what's
 * typed. For one line, use UI/Input.
 */
const meta = preview.meta({
    title: 'UI/Textarea',
    component: Textarea,
    parameters: {
        // Native attributes, not props: bound beside the args.
        attrs: { id: 'feedback', placeholder: 'What could we do better?' },
    },
    render: (args, { parameters }) => ({
        components: { Label, Textarea },
        setup: () => ({ args, attrs: parameters.attrs }),
        template: `
            <div class="grid max-w-md gap-2">
                <Label for="feedback">Feedback</Label>
                <Textarea v-bind="{ ...attrs, ...args }" />
            </div>`,
    }),
});
export default meta;

/** Empty, with a placeholder. Typing fills it. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        const field = canvas.getByRole('textbox', { name: 'Feedback' });

        await userEvent.type(field, 'The export to CSV is great.');

        await expect(field).toHaveValue('The export to CSV is great.');
    },
});

/** With text already in it. */
export const Filled = meta.story({
    args: {
        defaultValue:
            'We use Saucebase for our client portal.\nThe billing module saved us weeks.',
    },
});

/** Can't be edited. */
export const Disabled = meta.story({
    parameters: { attrs: { disabled: true } },
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('textbox')).toBeDisabled();
    },
});

/** Rejected by the server: a red edge. Pair it with a `FieldError` (UI/Field). */
export const Invalid = meta.story({
    parameters: { attrs: { 'aria-invalid': true } },
});
