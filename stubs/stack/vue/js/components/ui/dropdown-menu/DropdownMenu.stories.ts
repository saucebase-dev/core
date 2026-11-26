import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { expect, fn, screen, waitFor } from 'storybook/test';
import { ref } from 'vue';
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
} from '.';

const components = {
    Button,
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
};

type Args = { onSelect: (action: string) => void };

/**
 * A list of actions or options behind a button: a row's actions, the user menu, a view's
 * settings. Arrow keys move through it, typing jumps to an item.
 *
 * Items can be plain, `destructive`, checkboxes, a radio group, or open a submenu.
 */
const meta = preview.meta({
    title: 'UI/DropdownMenu',
    component: DropdownMenu,
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        default: { control: false },
    },
    args: { onSelect: fn() },
    render: (args: Args) => ({
        components,
        setup: () => ({ onSelect: args.onSelect }),
        template: `
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline">Project actions</Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-56">
                    <DropdownMenuLabel>Website redesign</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuGroup>
                        <DropdownMenuItem @select="onSelect('rename')">
                            Rename <DropdownMenuShortcut>⌘R</DropdownMenuShortcut>
                        </DropdownMenuItem>
                        <DropdownMenuItem @select="onSelect('duplicate')">
                            Duplicate <DropdownMenuShortcut>⌘D</DropdownMenuShortcut>
                        </DropdownMenuItem>
                        <DropdownMenuSub>
                            <DropdownMenuSubTrigger>Move to</DropdownMenuSubTrigger>
                            <DropdownMenuSubContent>
                                <DropdownMenuItem>Marketing</DropdownMenuItem>
                                <DropdownMenuItem>Engineering</DropdownMenuItem>
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>
                        <DropdownMenuItem disabled>Export (Pro plan)</DropdownMenuItem>
                    </DropdownMenuGroup>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem variant="destructive" @select="onSelect('delete')">
                        Delete project
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>`,
    }),
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

        await expect(args.onSelect).toHaveBeenCalledWith('rename');
        await waitFor(() => expect(screen.queryByRole('menu')).toBeNull());
    },
});

/** Checkboxes and a radio group, for a view's settings. */
export const Options = meta.story({
    render: () => ({
        components,
        setup: () => ({
            showArchived: ref(false),
            showDrafts: ref(true),
            sort: ref('updated'),
        }),
        template: `
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline">View</Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-56">
                    <DropdownMenuLabel>Show</DropdownMenuLabel>
                    <DropdownMenuCheckboxItem v-model="showDrafts">Drafts</DropdownMenuCheckboxItem>
                    <DropdownMenuCheckboxItem v-model="showArchived">Archived</DropdownMenuCheckboxItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuLabel>Sort by</DropdownMenuLabel>
                    <DropdownMenuRadioGroup v-model="sort">
                        <DropdownMenuRadioItem value="updated">Last updated</DropdownMenuRadioItem>
                        <DropdownMenuRadioItem value="name">Name</DropdownMenuRadioItem>
                    </DropdownMenuRadioGroup>
                </DropdownMenuContent>
            </DropdownMenu>`,
    }),
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
