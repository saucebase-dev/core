import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import InputField from '@/components/ui/input/InputField.vue';
import { expect, screen, waitFor } from 'storybook/test';
import { Popover, PopoverContent, PopoverTrigger } from '.';

const components = {
    Button,
    InputField,
    Popover,
    PopoverContent,
    PopoverTrigger,
};

/**
 * Content floating beside its trigger, that the user can interact with: a small form,
 * a filter, a share link. Not modal: clicking elsewhere closes it. For a hint the user
 * only reads, use Tooltip.
 */
const meta = preview.meta({
    title: 'UI/Popover',
    component: Popover,
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        default: { control: false },
    },
    render: (args) => ({
        components,
        setup: () => ({ args }),
        template: `
            <Popover v-bind="args">
                <PopoverTrigger as-child>
                    <Button variant="outline">Set budget</Button>
                </PopoverTrigger>
                <PopoverContent class="grid gap-3">
                    <p class="text-sm font-medium">Monthly budget</p>
                    <p class="text-muted-foreground text-sm">We email you at 80%.</p>
                    <InputField label="Amount (USD)" name="budget" type="number" placeholder="500" />
                </PopoverContent>
            </Popover>`,
    }),
});
export default meta;

/** Closed until its trigger is clicked. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(
            canvas.getByRole('button', { name: 'Set budget' }),
        );
        const title = await screen.findByText('Monthly budget');

        await waitFor(() => expect(title).toBeVisible());
    },
});

/** Open from the start. */
export const Open = meta.story({
    args: { defaultOpen: true },
    parameters: { docs: { story: { inline: false, iframeHeight: '260px' } } },
});
