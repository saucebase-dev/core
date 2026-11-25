import preview from '@/.storybook/preview';
import { Skeleton } from './skeleton';

/**
 * A pulsing placeholder the shape of content that's still loading, so the page doesn't
 * jump when it arrives. Size it with classes to match what it stands in for.
 */
const meta = preview.meta({
    title: 'UI/Skeleton',
    component: Skeleton,
    args: { className: 'h-4 w-48' },
});
export default meta;

/** One line of text. */
export const Line = meta.story({});

/** A list row: avatar and two lines. */
export const ListRow = meta.story({
    render: () => (
        <div className="flex items-center gap-4">
            <Skeleton className="size-10 rounded-full" />
            <div className="grid gap-2">
                <Skeleton className="h-4 w-48" />
                <Skeleton className="h-4 w-32" />
            </div>
        </div>
    ),
});

/** A card still loading. */
export const CardPlaceholder = meta.story({
    render: () => (
        <div className="grid max-w-sm gap-3">
            <Skeleton className="h-40 w-full rounded-xl" />
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-4 w-1/2" />
        </div>
    ),
});
