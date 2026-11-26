import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import InputField from '@/components/ui/input/InputField.vue';
import { expect, screen, waitFor } from 'storybook/test';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
    DialogTrigger,
} from '.';

const components = {
    Button,
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
    DialogTrigger,
    InputField,
};

/**
 * A modal window for a short task that shouldn't leave the page: rename, invite, edit a
 * detail. Esc, the close button and a click outside close it. For a yes/no question,
 * use AlertDialog.
 *
 * `DialogScrollContent` instead of `DialogContent` when the content may be taller than
 * the screen: the whole dialog scrolls.
 */
const meta = preview.meta({
    title: 'UI/Dialog',
    component: Dialog,
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        default: { control: false },
    },
    render: (args) => ({
        components,
        setup: () => ({ args }),
        template: `
            <Dialog v-bind="args">
                <DialogTrigger as-child>
                    <Button variant="outline">Invite member</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Invite a member</DialogTitle>
                        <DialogDescription>They get an email with a link to join Acme Inc.</DialogDescription>
                    </DialogHeader>
                    <InputField label="Email" name="email" type="email" placeholder="you@example.com" />
                    <DialogFooter>
                        <DialogClose as-child>
                            <Button variant="outline">Cancel</Button>
                        </DialogClose>
                        <Button>Send invite</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>`,
    }),
});
export default meta;

/** Closed until its trigger is clicked; the close button closes it again. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(
            canvas.getByRole('button', { name: 'Invite member' }),
        );
        const dialog = await screen.findByRole('dialog');
        await waitFor(() => expect(dialog).toBeVisible());
        await expect(dialog).toHaveTextContent('Invite a member');

        await userEvent.click(screen.getByRole('button', { name: 'Close' }));

        await waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());
    },
});

/** Open from the start. */
export const Open = meta.story({
    args: { defaultOpen: true },
    parameters: { docs: { story: { inline: false, iframeHeight: '360px' } } },
});

/** Content taller than the screen: the dialog scrolls as a whole. */
export const LongContent = meta.story({
    args: { defaultOpen: true },
    parameters: { docs: { story: { inline: false, iframeHeight: '480px' } } },
    render: (args) => ({
        components,
        setup: () => ({ args }),
        template: `
            <Dialog v-bind="args">
                <DialogTrigger as-child>
                    <Button variant="outline">Read the terms</Button>
                </DialogTrigger>
                <DialogScrollContent>
                    <DialogHeader>
                        <DialogTitle>Terms of service</DialogTitle>
                        <DialogDescription>Last updated 1 November 2026.</DialogDescription>
                    </DialogHeader>
                    <p v-for="n in 12" :key="n" class="text-muted-foreground text-sm">
                        {{ n }}. You keep ownership of everything you upload. We store it only to run
                        the service for you, and delete it within 30 days of you closing your account.
                    </p>
                    <DialogFooter>
                        <DialogClose as-child>
                            <Button>I agree</Button>
                        </DialogClose>
                    </DialogFooter>
                </DialogScrollContent>
            </Dialog>`,
    }),
    play: async () => {
        await expect(
            await screen.findByText('Terms of service'),
        ).toBeInTheDocument();
    },
});
