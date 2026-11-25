import preview from '@/.storybook/preview';
import { Label } from '@/components/ui/label';
import { expect } from 'storybook/test';
import { Checkbox } from './checkbox';

/**
 * A yes-or-no choice that takes effect when the form is sent: accept the terms, email me
 * updates. Always beside a label; for a setting that applies at once, use a switch.
 */
const meta = preview.meta({
    title: 'UI/Checkbox',
    component: Checkbox,
    args: { id: 'updates' },
    render: (args) => (
        <div className="flex items-center gap-2">
            <Checkbox {...args} />
            <Label htmlFor="updates">Email me product updates</Label>
        </div>
    ),
});
export default meta;

/** Unchecked. Clicking it, or its label, checks it. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        const box = canvas.getByRole('checkbox');
        await expect(box).not.toBeChecked();

        await userEvent.click(canvas.getByText('Email me product updates'));

        await expect(box).toBeChecked();
    },
});

/** Already checked. */
export const Checked = meta.story({ args: { defaultChecked: true } });

/** Some, not all, of a group: the "select all" box of a table. */
export const Indeterminate = meta.story({
    args: { defaultChecked: 'indeterminate' },
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
    render: (args) => (
        <div className="flex items-center gap-2">
            <Checkbox {...args} aria-invalid="true" />
            <Label htmlFor="updates">I accept the terms of service</Label>
        </div>
    ),
});
