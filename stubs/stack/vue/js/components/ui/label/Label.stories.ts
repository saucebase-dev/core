import preview from '@/.storybook/preview';
import { Input } from '@/components/ui/input';
import { expect } from 'storybook/test';
import { Label } from '.';

/**
 * Names a form control. Point `for` at the control's `id`, so clicking the label focuses
 * it and screen readers read the name with it. In forms, prefer `FieldLabel` inside a
 * `Field` (UI/Field), which also lays out the description and error.
 */
const meta = preview.meta({
    title: 'UI/Label',
    component: Label,
    args: { for: 'workspace' },
    render: (args) => ({
        components: { Input, Label },
        setup: () => ({ args }),
        template: `
            <div class="grid max-w-sm gap-2">
                <Label v-bind="args">Workspace name</Label>
                <Input id="workspace" placeholder="Acme Inc." />
            </div>`,
    }),
});
export default meta;

/** Beside its input. Clicking it focuses the input. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(canvas.getByText('Workspace name'));

        await expect(
            canvas.getByRole('textbox', { name: 'Workspace name' }),
        ).toHaveFocus();
    },
});

/** For a disabled input: it dims with it. */
export const Disabled = meta.story({
    render: (args) => ({
        components: { Input, Label },
        setup: () => ({ args }),
        template: `
            <div class="grid max-w-sm gap-2">
                <Input id="workspace" class="peer order-2" disabled placeholder="Acme Inc." />
                <Label v-bind="args" class="order-1">Workspace name</Label>
            </div>`,
    }),
});
