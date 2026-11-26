import preview from '@/.storybook/preview';
import { Inbox } from 'lucide-react';
import { expect } from 'storybook/test';
import { Button } from './button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from './empty';

/**
 * The parts of an empty state, to compose: media, title, description, actions. For the
 * usual icon, title, line and action, UI/EmptyState does it in one component.
 */
const meta = preview.meta({
    title: 'UI/Empty',
    component: Empty,
    render: () => (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <Inbox />
                </EmptyMedia>
                <EmptyTitle>No messages</EmptyTitle>
                <EmptyDescription>
                    When customers write in, their messages land here.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <Button>Share your support address</Button>
            </EmptyContent>
        </Empty>
    ),
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
    render: () => (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia>
                    <Inbox className="text-muted-foreground size-12" />
                </EmptyMedia>
                <EmptyTitle>Inbox zero</EmptyTitle>
                <EmptyDescription>Nothing left to answer.</EmptyDescription>
            </EmptyHeader>
        </Empty>
    ),
});
