import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { expect, screen, waitFor } from 'storybook/test';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '.';

const components = {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
    Button,
};

/**
 * A modal question that needs an answer before the user goes on: it can't be dismissed
 * by clicking outside. For a plain confirmation from code, use App/DynamicDialog, which
 * wraps this; compose it yourself only for custom content.
 */
const meta = preview.meta({
    title: 'UI/AlertDialog',
    component: AlertDialog,
    render: (args) => ({
        components,
        setup: () => ({ args }),
        template: `
            <AlertDialog v-bind="args">
                <AlertDialogTrigger as-child>
                    <Button variant="outline">Leave workspace</Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Leave Acme Inc.?</AlertDialogTitle>
                        <AlertDialogDescription>
                            You lose access to its projects until someone invites you again.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Stay</AlertDialogCancel>
                        <AlertDialogAction>Leave</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>`,
    }),
});
export default meta;

/** Closed until its trigger is clicked; Cancel closes it again. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(
            canvas.getByRole('button', { name: 'Leave workspace' }),
        );
        const dialog = await screen.findByRole('alertdialog');
        await waitFor(() => expect(dialog).toBeVisible());

        await userEvent.click(screen.getByRole('button', { name: 'Stay' }));

        await waitFor(() =>
            expect(screen.queryByRole('alertdialog')).toBeNull(),
        );
    },
});

/** Open from the start, as when a page opens it itself. */
export const Open = meta.story({
    args: { defaultOpen: true },
    parameters: { docs: { story: { inline: false, iframeHeight: '320px' } } },
    play: async () => {
        await expect(
            await screen.findByText('Leave Acme Inc.?'),
        ).toBeInTheDocument();
    },
});
