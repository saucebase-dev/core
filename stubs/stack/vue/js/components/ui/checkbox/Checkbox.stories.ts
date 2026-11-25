import preview from '@/.storybook/preview';
import { Label } from '@/components/ui/label';
import { expect } from 'storybook/test';
import { ref, useId } from 'vue';
import { Checkbox } from '.';

/**
 * A yes-or-no choice that takes effect when the form is sent: accept the terms, email me
 * updates. Always beside a label; for a setting that applies at once, use a switch.
 */
const meta = preview.meta({
    title: 'UI/Checkbox',
    component: Checkbox,
    // Each example's own id (useId): the docs page shows them all, and a shared one would
    // send every label's click to the first box.
    render: (args) => ({
        components: { Checkbox, Label },
        setup: () => ({ args, id: useId() }),
        template: `
            <div class="flex items-center gap-2">
                <Checkbox v-bind="args" :id="id" />
                <Label :for="id">Email me product updates</Label>
            </div>`,
    }),
});
export default meta;

/** Unchecked. Clicking it, or its label, checks it. */
export const Default = meta.story({
    render: (args) => ({
        components: { Checkbox, Label },
        setup: () => ({ args, id: useId(), checked: ref(false) }),
        template: `
            <div class="flex items-center gap-2">
                <Checkbox v-bind="args" :id="id" v-model="checked" />
                <Label :for="id">Email me product updates</Label>
            </div>`,
    }),
    play: async ({ canvas, userEvent }) => {
        const box = canvas.getByRole('checkbox');
        await expect(box).not.toBeChecked();

        await userEvent.click(canvas.getByText('Email me product updates'));

        await expect(box).toBeChecked();
    },
});

/** Already checked. */
export const Checked = meta.story({ args: { defaultValue: true } });

/** Some, not all, of a group: the "select all" box of a table. */
export const Indeterminate = meta.story({
    args: { defaultValue: 'indeterminate' },
});

/** Can't be changed; its label dims with it. */
export const Disabled = meta.story({
    args: { disabled: true },
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('checkbox')).toBeDisabled();
    },
});

/** Required and left unchecked when the form was sent. */
export const Invalid = meta.story({
    render: (args) => ({
        components: { Checkbox, Label },
        setup: () => ({ args, id: useId() }),
        template: `
            <div class="flex items-center gap-2">
                <Checkbox v-bind="args" :id="id" aria-invalid="true" />
                <Label :for="id">I accept the terms of service</Label>
            </div>`,
    }),
});
