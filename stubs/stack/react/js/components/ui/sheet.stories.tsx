import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { ComponentProps } from 'react';
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
} from './sheet';

type Args = ComponentProps<typeof Sheet> & {
    side?: 'top' | 'right' | 'bottom' | 'left';
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
    args: { side: 'right' } as Args,
    argTypes: {
        side: {
            control: 'select',
            options: ['right', 'left', 'top', 'bottom'],
        },
    },
    render: ({ side, ...root }: Args) => (
        <Sheet {...root}>
            <SheetTrigger asChild>
                <Button variant="outline">Edit profile</Button>
            </SheetTrigger>
            <SheetContent side={side}>
                <SheetHeader>
                    <SheetTitle>Edit profile</SheetTitle>
                    <SheetDescription>
                        Changes show to your team straight away.
                    </SheetDescription>
                </SheetHeader>
                <div className="grid gap-4 px-4">
                    <Field>
                        <FieldLabel htmlFor="profile-name">Name</FieldLabel>
                        <Input
                            id="profile-name"
                            name="name"
                            placeholder="Ada Lovelace"
                        />
                    </Field>
                </div>
                <SheetFooter>
                    <SheetClose asChild>
                        <Button>Save</Button>
                    </SheetClose>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    ),
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
    args: { side: 'left', defaultOpen: true } as Args,
    parameters: { docs: { story: { inline: false, iframeHeight: '420px' } } },
});

/** From the top. */
export const Top = meta.story({
    args: { side: 'top', defaultOpen: true } as Args,
    parameters: { docs: { story: { inline: false, iframeHeight: '420px' } } },
});

/** From the bottom, often for actions on a phone. */
export const Bottom = meta.story({
    args: { side: 'bottom', defaultOpen: true } as Args,
    parameters: { docs: { story: { inline: false, iframeHeight: '420px' } } },
});
