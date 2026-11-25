import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';
import { expect, screen, waitFor } from 'storybook/test';
import { Toaster } from './sonner';

/**
 * Short notices in a corner that go away by themselves: saved, copied, failed. The app
 * renders one Toaster; code calls `toast.success('Saved')` from `sonner`, and the server
 * sends one with a `toast` flash. It follows the app's theme.
 */
const meta = preview.meta({
    title: 'UI/Toaster',
    component: Toaster,
    parameters: { docs: { story: { inline: false, iframeHeight: '320px' } } },
    render: () => (
        <>
            <div className="flex flex-wrap gap-2">
                <Button
                    variant="outline"
                    onClick={() => toast.success('Profile saved')}
                >
                    Success
                </Button>
                <Button
                    variant="outline"
                    onClick={() =>
                        toast.info('Invitations expire after 7 days', {
                            description: 'Resend one from the Members page.',
                        })
                    }
                >
                    Info
                </Button>
                <Button
                    variant="outline"
                    onClick={() => toast.warning('Your trial ends in 3 days')}
                >
                    Warning
                </Button>
                <Button
                    variant="outline"
                    onClick={() => toast.error('Failed to copy')}
                >
                    Error
                </Button>
                <Button
                    variant="outline"
                    onClick={() =>
                        toast('Project archived', {
                            action: { label: 'Undo', onClick: () => {} },
                        })
                    }
                >
                    With action
                </Button>
            </div>
            <Toaster />
        </>
    ),
});
export default meta;

/** Each kind of toast, from its button. */
export const Kinds = meta.story({
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(canvas.getByRole('button', { name: 'Success' }));

        const toast = await screen.findByText('Profile saved');

        // Toasts slide in.
        await waitFor(() => expect(toast).toBeVisible());
    },
});
