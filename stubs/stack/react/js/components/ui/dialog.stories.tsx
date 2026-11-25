import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { expect, screen, waitFor } from 'storybook/test';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from './dialog';

/**
 * A modal window for a short task that shouldn't leave the page: rename, invite, edit a
 * detail. Esc, the close button and a click outside close it. For a yes/no question,
 * use AlertDialog.
 *
 * When the content may be taller than the screen, give `DialogContent` a maximum height
 * and let it scroll (`max-h-[calc(100vh-4rem)] overflow-y-auto`).
 */
const meta = preview.meta({
    title: 'UI/Dialog',
    component: Dialog,
    render: (args) => (
        <Dialog {...args}>
            <DialogTrigger asChild>
                <Button variant="outline">Invite member</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Invite a member</DialogTitle>
                    <DialogDescription>
                        They get an email with a link to join Acme Inc.
                    </DialogDescription>
                </DialogHeader>
                <Field>
                    <FieldLabel htmlFor="invite-email">Email</FieldLabel>
                    <Input
                        id="invite-email"
                        name="email"
                        type="email"
                        placeholder="you@example.com"
                    />
                </Field>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button>Send invite</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    ),
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
    render: (args) => (
        <Dialog {...args}>
            <DialogTrigger asChild>
                <Button variant="outline">Read the terms</Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100vh-4rem)] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Terms of service</DialogTitle>
                    <DialogDescription>
                        Last updated 1 November 2026.
                    </DialogDescription>
                </DialogHeader>
                {Array.from({ length: 12 }, (_, i) => (
                    <p key={i} className="text-muted-foreground text-sm">
                        {i + 1}. You keep ownership of everything you upload. We
                        store it only to run the service for you, and delete it
                        within 30 days of you closing your account.
                    </p>
                ))}
                <DialogFooter>
                    <DialogClose asChild>
                        <Button>I agree</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    ),
    play: async () => {
        await expect(
            await screen.findByText('Terms of service'),
        ).toBeInTheDocument();
    },
});
