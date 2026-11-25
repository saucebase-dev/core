import preview from '@/.storybook/preview';
import { Label } from '@/components/ui/label';
import { useId, type ComponentProps } from 'react';
import { expect } from 'storybook/test';
import { Checkbox } from './checkbox';

// A box and its label, joined by an id of their own: the docs page shows every example
// at once, and a shared id would send each label's click to the first box.
function Labelled({
    label,
    ...props
}: ComponentProps<typeof Checkbox> & { label: string }) {
    const id = useId();

    return (
        <div className="flex items-center gap-2">
            <Checkbox {...props} id={id} />
            <Label htmlFor={id}>{label}</Label>
        </div>
    );
}

/**
 * A yes-or-no choice that takes effect when the form is sent: accept the terms, email me
 * updates. Always beside a label; for a setting that applies at once, use a switch.
 */
const meta = preview.meta({
    title: 'UI/Checkbox',
    component: Checkbox,
    render: (args) => <Labelled {...args} label="Email me product updates" />,
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
    play: async ({ canvas }) => {
        const box = canvas.getByRole('checkbox');
        await expect(box).toHaveAttribute('aria-checked', 'mixed');
        // A dash, not a tick: a tick would read as "all of them".
        const icon = [...box.querySelectorAll('svg')].find((svg) =>
            svg.checkVisibility(),
        );
        await expect(icon?.getAttribute('class')).toMatch(/minus/);
    },
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
        <Labelled
            {...args}
            aria-invalid="true"
            label="I accept the terms of service"
        />
    ),
});
