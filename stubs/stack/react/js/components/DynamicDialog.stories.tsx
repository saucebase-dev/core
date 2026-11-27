import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import {
    useDialog,
    type ConfirmOptions,
} from '@saucebase/react/hooks/useDialog';
import { Trash2 } from 'lucide-react';
import { expect, fn, screen } from 'storybook/test';
import DynamicDialog from './DynamicDialog';

type Args = ConfirmOptions & { onAnswer: (confirmed: boolean) => void };

function Demo({ onAnswer, ...options }: Args) {
    const { confirm } = useDialog();

    return (
        <>
            <Button
                variant="outline"
                onClick={async () => onAnswer(await confirm(options))}
            >
                Open
            </Button>
            <DynamicDialog />
        </>
    );
}

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
    parameters: {
        // useDialog() keeps one dialog for the page: inline, every example's button would
        // open them all.
        docs: { story: { inline: false, iframeHeight: '320px' } },
    },
    args: {
        title: 'Archive this project?',
        description: 'Its members lose access until you restore it.',
        onAnswer: fn(),
    },
    render: (args) => <Demo {...(args as Args)} />,
});
export default meta;

/** A plain question. Cancel answers `false`. */
export const Default = meta.story({
    play: async ({ args, canvas, userEvent }) => {
        await userEvent.click(canvas.getByRole('button', { name: 'Open' }));
        const dialog = await screen.findByRole('alertdialog');
        await expect(dialog).toHaveTextContent('Archive this project?');

        await userEvent.click(screen.getByTestId('confirm-dialog-cancel'));

        await expect((args as Args).onAnswer).toHaveBeenCalledWith(false);
    },
});

/** For what deletes or can't be undone: a red confirm button and icon. Confirm answers `true`. */
export const Destructive = meta.story({
    args: {
        title: 'Delete this project?',
        description: 'Its files and history are deleted for good.',
        confirmLabel: 'Delete',
        variant: 'destructive',
        icon: Trash2,
    },
    play: async ({ args, canvas, userEvent }) => {
        await userEvent.click(canvas.getByRole('button', { name: 'Open' }));
        await userEvent.click(
            await screen.findByTestId('confirm-dialog-confirm'),
        );

        await expect((args as Args).onAnswer).toHaveBeenCalledWith(true);
    },
});

/** The icon beside the text instead of above it. */
export const LeftAligned = meta.story({
    args: { icon: Trash2, align: 'left' },
});
