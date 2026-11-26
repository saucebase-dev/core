import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import InputField from '@/components/ui/input/InputField.vue';
import { expect, screen, waitFor } from 'storybook/test';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '.';

const components = {
    Button,
    InputField,
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
};

type Args = {
    side?: 'top' | 'right' | 'bottom' | 'left';
    defaultOpen?: boolean;
};

/**
 * A panel that slides in from an edge, over the page: details, filters, a form longer
 * than a dialog should hold. The sidebar becomes one on phones.
 *
 * `side` on `SheetContent`: `right` (the default), `left`, `top` or `bottom`.
 */
const meta = preview.meta({
    title: 'UI/Sheet',
    component: Sheet,
    args: { side: 'right' },
    argTypes: {
        side: {
            control: 'select',
            options: ['right', 'left', 'top', 'bottom'],
        },
        // The story fills it itself: its control would do nothing.
        default: { control: false },
    },
    render: (args: Args) => ({
        components,
        setup: () => {
            const { side, ...root } = args;

            return { root, side };
        },
        template: `
            <Sheet v-bind="root">
                <SheetTrigger as-child>
                    <Button variant="outline">Edit profile</Button>
                </SheetTrigger>
                <SheetContent :side="side">
                    <SheetHeader>
                        <SheetTitle>Edit profile</SheetTitle>
                        <SheetDescription>Changes show to your team straight away.</SheetDescription>
                    </SheetHeader>
                    <div class="grid gap-4 px-4">
                        <InputField label="Name" name="name" placeholder="Ada Lovelace" />
                    </div>
                    <SheetFooter>
                        <SheetClose as-child>
                            <Button>Save</Button>
                        </SheetClose>
                    </SheetFooter>
                </SheetContent>
            </Sheet>`,
    }),
});
export default meta;

/** From the right, the default. Opens from its trigger; the close button closes it. */
export const Right = meta.story({
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(
            canvas.getByRole('button', { name: 'Edit profile' }),
        );
        const sheet = await screen.findByRole('dialog');
        await waitFor(() => expect(sheet).toBeVisible());

        await userEvent.click(screen.getByRole('button', { name: 'Close' }));

        await waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());
    },
});

/** From the left, as the sidebar on a phone. */
export const Left = meta.story({
    args: { side: 'left', defaultOpen: true },
    parameters: { docs: { story: { inline: false, iframeHeight: '420px' } } },
});

/** From the top. */
export const Top = meta.story({
    args: { side: 'top', defaultOpen: true },
    parameters: { docs: { story: { inline: false, iframeHeight: '420px' } } },
});

/** From the bottom, often for actions on a phone. */
export const Bottom = meta.story({
    args: { side: 'bottom', defaultOpen: true },
    parameters: { docs: { story: { inline: false, iframeHeight: '420px' } } },
});
