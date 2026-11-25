import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { expect, screen, waitFor } from 'storybook/test';
import { toast } from 'vue-sonner';
import { Toaster } from '.';

/**
 * Short notices in a corner that go away by themselves: saved, copied, failed. The app
 * renders one Toaster; code calls `toast.success('Saved')` from `vue-sonner`, and the
 * server sends one with a `toast` flash. It follows the app's theme.
 */
const meta = preview.meta({
    title: 'UI/Toaster',
    component: Toaster,
    parameters: { docs: { story: { inline: false, iframeHeight: '320px' } } },
    render: () => ({
        components: { Button, Toaster },
        setup: () => ({
            success: () => toast.success('Profile saved'),
            info: () =>
                toast.info('Invitations expire after 7 days', {
                    description: 'Resend one from the Members page.',
                }),
            warning: () => toast.warning('Your trial ends in 3 days'),
            error: () => toast.error('Failed to copy'),
            action: () =>
                toast('Project archived', {
                    action: { label: 'Undo', onClick: () => {} },
                }),
        }),
        template: `
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" @click="success">Success</Button>
                <Button variant="outline" @click="info">Info</Button>
                <Button variant="outline" @click="warning">Warning</Button>
                <Button variant="outline" @click="error">Error</Button>
                <Button variant="outline" @click="action">With action</Button>
            </div>
            <Toaster />`,
    }),
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
