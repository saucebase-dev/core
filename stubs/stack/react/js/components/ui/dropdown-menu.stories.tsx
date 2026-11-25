import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { useState, type ComponentProps } from 'react';
import { expect, fn, screen, waitFor } from 'storybook/test';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from './dropdown-menu';

type Args = ComponentProps<typeof DropdownMenu> & {
    onSelect: (action: string) => void;
};

/**
 * A list of actions or options behind a button: a row's actions, the user menu, a view's
 * settings. Arrow keys move through it, typing jumps to an item.
 *
 * Items can be plain, `destructive`, checkboxes, a radio group, or open a submenu.
 */
const meta = preview.meta({
    title: 'UI/DropdownMenu',
    component: DropdownMenu,
    args: { onSelect: fn() } as Args,
    render: ({ onSelect }: Args) => (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline">Project actions</Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-56">
                <DropdownMenuLabel>Website redesign</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                    <DropdownMenuItem onSelect={() => onSelect('rename')}>
                        Rename <DropdownMenuShortcut>⌘R</DropdownMenuShortcut>
                    </DropdownMenuItem>
                    <DropdownMenuItem onSelect={() => onSelect('duplicate')}>
                        Duplicate{' '}
                        <DropdownMenuShortcut>⌘D</DropdownMenuShortcut>
                    </DropdownMenuItem>
                    <DropdownMenuSub>
                        <DropdownMenuSubTrigger>Move to</DropdownMenuSubTrigger>
                        <DropdownMenuSubContent>
                            <DropdownMenuItem>Marketing</DropdownMenuItem>
                            <DropdownMenuItem>Engineering</DropdownMenuItem>
                        </DropdownMenuSubContent>
                    </DropdownMenuSub>
                    <DropdownMenuItem disabled>
                        Export (Pro plan)
                    </DropdownMenuItem>
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    variant="destructive"
                    onSelect={() => onSelect('delete')}
                >
                    Delete project
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    ),
});
export default meta;

/** Actions, a submenu, a disabled item and a destructive one. Picking an item closes the menu. */
export const Actions = meta.story({
    play: async ({ args, canvas, userEvent }) => {
        await userEvent.click(
            canvas.getByRole('button', { name: 'Project actions' }),
        );
        const rename = await screen.findByRole('menuitem', { name: /Rename/ });
        await waitFor(() => expect(rename).toBeVisible());

        await userEvent.click(rename);

        await expect((args as Args).onSelect).toHaveBeenCalledWith('rename');
        await waitFor(() => expect(screen.queryByRole('menu')).toBeNull());
    },
});

function ViewOptions() {
    const [showDrafts, setShowDrafts] = useState(true);
    const [showArchived, setShowArchived] = useState(false);
    const [sort, setSort] = useState('updated');

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline">View</Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-56">
                <DropdownMenuLabel>Show</DropdownMenuLabel>
                <DropdownMenuCheckboxItem
                    checked={showDrafts}
                    onCheckedChange={setShowDrafts}
                >
                    Drafts
                </DropdownMenuCheckboxItem>
                <DropdownMenuCheckboxItem
                    checked={showArchived}
                    onCheckedChange={setShowArchived}
                >
                    Archived
                </DropdownMenuCheckboxItem>
                <DropdownMenuSeparator />
                <DropdownMenuLabel>Sort by</DropdownMenuLabel>
                <DropdownMenuRadioGroup value={sort} onValueChange={setSort}>
                    <DropdownMenuRadioItem value="updated">
                        Last updated
                    </DropdownMenuRadioItem>
                    <DropdownMenuRadioItem value="name">
                        Name
                    </DropdownMenuRadioItem>
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/** Checkboxes and a radio group, for a view's settings. */
export const Options = meta.story({
    render: () => <ViewOptions />,
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(canvas.getByRole('button', { name: 'View' }));
        const archived = await screen.findByRole('menuitemcheckbox', {
            name: 'Archived',
        });
        await waitFor(() => expect(archived).toBeVisible());

        await expect(archived).toHaveAttribute('aria-checked', 'false');
        await expect(
            screen.getByRole('menuitemradio', { name: 'Last updated' }),
        ).toHaveAttribute('aria-checked', 'true');
    },
});
