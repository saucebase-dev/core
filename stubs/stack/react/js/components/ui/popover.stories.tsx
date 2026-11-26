import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { expect, screen, waitFor } from 'storybook/test';
import { Popover, PopoverContent, PopoverTrigger } from './popover';

/**
 * Content floating beside its trigger, that the user can interact with: a small form,
 * a filter, a share link. Not modal: clicking elsewhere closes it. For a hint the user
 * only reads, use Tooltip.
 */
const meta = preview.meta({
    title: 'UI/Popover',
    component: Popover,
    // Props the story fills itself: their controls would do nothing.
    argTypes: {
        children: { control: false },
    },
    render: (args) => (
        <Popover {...args}>
            <PopoverTrigger asChild>
                <Button variant="outline">Set budget</Button>
            </PopoverTrigger>
            <PopoverContent className="grid gap-3">
                <p className="text-sm font-medium">Monthly budget</p>
                <p className="text-muted-foreground text-sm">
                    We email you at 80%.
                </p>
                <Field>
                    <FieldLabel htmlFor="budget">Amount (USD)</FieldLabel>
                    <Input
                        id="budget"
                        name="budget"
                        type="number"
                        placeholder="500"
                    />
                </Field>
            </PopoverContent>
        </Popover>
    ),
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
