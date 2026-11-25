import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import {
    useDialog,
    type ConfirmOptions,
} from '@saucebase/vue/composables/useDialog';
import { expect, fn, screen } from 'storybook/test';
import IconTrash from '~icons/lucide/trash-2';
import DynamicDialog from './DynamicDialog.vue';

type Args = ConfirmOptions & { onAnswer: (confirmed: boolean) => void };

/**
 * Asks the user to confirm before an action goes ahead. The app renders it once; code
 * opens it with `await useDialog().confirm({ title, ... })`, which resolves to `true` or
 * `false`.
 *
 * `destructive` for what deletes or can't be undone. The stories open it from a button,
 * with the options as args.
 */
const meta = preview.meta({
    title: 'App/DynamicDialog',
    component: DynamicDialog,
    args: {
        title: 'Archive this project?',
        description: 'Its members lose access until you restore it.',
        onAnswer: fn(),
    },
    render: (args: Args) => ({
        components: { Button, DynamicDialog },
        setup() {
            const { confirm } = useDialog();
            const { onAnswer, ...options } = args;
            const open = async () => onAnswer(await confirm(options));

            return { open };
        },
        template: `
            <Button variant="outline" @click="open">Open</Button>
            <DynamicDialog />`,
    }),
});
export default meta;

/** A plain question. Cancel answers `false`. */
export const Default = meta.story({
    play: async ({ args, canvas, userEvent }) => {
        await userEvent.click(canvas.getByRole('button', { name: 'Open' }));
        const dialog = await screen.findByRole('alertdialog');
        await expect(dialog).toHaveTextContent('Archive this project?');

        await userEvent.click(screen.getByTestId('confirm-dialog-cancel'));

        await expect(args.onAnswer).toHaveBeenCalledWith(false);
    },
});

/** For what deletes or can't be undone: a red confirm button and icon. Confirm answers `true`. */
export const Destructive = meta.story({
    args: {
        title: 'Delete this project?',
        description: 'Its files and history are deleted for good.',
        confirmLabel: 'Delete',
        variant: 'destructive',
        icon: IconTrash,
    },
    play: async ({ args, canvas, userEvent }) => {
        await userEvent.click(canvas.getByRole('button', { name: 'Open' }));
        await userEvent.click(
            await screen.findByTestId('confirm-dialog-confirm'),
        );

        await expect(args.onAnswer).toHaveBeenCalledWith(true);
    },
});

/** The icon beside the text instead of above it. */
export const LeftAligned = meta.story({
    args: { icon: IconTrash, align: 'left' },
});
