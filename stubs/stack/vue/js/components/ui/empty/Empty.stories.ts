import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { expect } from 'storybook/test';
import IconInbox from '~icons/lucide/inbox';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '.';

const components = {
    Button,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    IconInbox,
};

/**
 * The parts of an empty state, to compose: media, title, description, actions. For the
 * usual icon, title, line and action, UI/EmptyState does it in one component.
 */
const meta = preview.meta({
    title: 'UI/Empty',
    component: Empty,
    render: () => ({
        components,
        template: `
            <Empty class="border">
                <EmptyHeader>
                    <EmptyMedia variant="icon"><IconInbox /></EmptyMedia>
                    <EmptyTitle>No messages</EmptyTitle>
                    <EmptyDescription>When customers write in, their messages land here.</EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button>Share your support address</Button>
                </EmptyContent>
            </Empty>`,
    }),
});
export default meta;

/** An icon in a tile, with an action. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByText('No messages')).toBeVisible();
    },
});

/** The media as it is (an illustration, a large icon), without the tile. */
export const PlainMedia = meta.story({
    render: () => ({
        components,
        template: `
            <Empty class="border">
                <EmptyHeader>
                    <EmptyMedia><IconInbox class="text-muted-foreground size-12" /></EmptyMedia>
                    <EmptyTitle>Inbox zero</EmptyTitle>
                    <EmptyDescription>Nothing left to answer.</EmptyDescription>
                </EmptyHeader>
            </Empty>`,
    }),
});
