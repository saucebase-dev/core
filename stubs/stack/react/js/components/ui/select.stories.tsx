import preview from '@/.storybook/preview';
import { expect, screen, waitFor } from 'storybook/test';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
} from './select';

/**
 * Picks one option from a short list: a role, a plan, a status.
 *
 * Give it a label: `aria-label` on the trigger, or a `<label>` beside it.
 */
const meta = preview.meta({
    title: 'UI/Select',
    component: Select,
    // Props the story fills itself: their controls would do nothing.
    argTypes: {
        children: { control: false },
    },
    render: (args) => (
        <Select {...args}>
            <SelectTrigger className="w-48" aria-label="Role">
                <SelectValue placeholder="Choose a role" />
            </SelectTrigger>
            <SelectContent>
                <SelectGroup>
                    <SelectLabel>Workspace</SelectLabel>
                    <SelectItem value="owner">Owner</SelectItem>
                    <SelectItem value="admin">Admin</SelectItem>
                    <SelectItem value="member">Member</SelectItem>
                </SelectGroup>
                <SelectSeparator />
                <SelectGroup>
                    <SelectLabel>External</SelectLabel>
                    <SelectItem value="guest">Guest</SelectItem>
                    <SelectItem value="billing" disabled>
                        Billing contact (Pro plan)
                    </SelectItem>
                </SelectGroup>
            </SelectContent>
        </Select>
    ),
});
export default meta;

/** Nothing picked yet: the placeholder. Picking an option shows it on the trigger. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        const trigger = canvas.getByRole('combobox', { name: 'Role' });
        await expect(trigger).toHaveTextContent('Choose a role');

        await userEvent.click(trigger);
        const admin = await screen.findByRole('option', { name: 'Admin' });
        await waitFor(() => expect(admin).toBeVisible());
        await userEvent.click(admin);

        await waitFor(() => expect(trigger).toHaveTextContent('Admin'));
    },
});

/** With a value already picked. */
export const WithValue = meta.story({
    args: { defaultValue: 'member' },
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('combobox', { name: 'Role' }),
        ).toHaveTextContent('Member');
    },
});

/** Disabled: the value shows, but can't be changed. */
export const Disabled = meta.story({
    args: { defaultValue: 'owner', disabled: true },
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('combobox', { name: 'Role' }),
        ).toBeDisabled();
    },
});
