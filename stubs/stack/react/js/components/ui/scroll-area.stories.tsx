import preview from '@/.storybook/preview';
import { Fragment } from 'react';
import { expect } from 'storybook/test';
import { ScrollArea, ScrollBar } from './scroll-area';
import { Separator } from './separator';

const releases = Array.from({ length: 30 }, (_, i) => `v3.${30 - i}.0`);

/**
 * A fixed-size box whose content scrolls, with a thin scrollbar that matches the theme
 * on every platform. Give it a height (or width) and put the long content inside.
 */
const meta = preview.meta({
    title: 'UI/ScrollArea',
    component: ScrollArea,
    render: () => (
        <ScrollArea className="h-72 w-48 rounded-md border">
            <div className="p-4">
                <h4 className="mb-4 text-sm font-medium">Releases</h4>
                {releases.map((release) => (
                    <Fragment key={release}>
                        <div className="text-sm">{release}</div>
                        <Separator className="my-2" />
                    </Fragment>
                ))}
            </div>
        </ScrollArea>
    ),
});
export default meta;

/** A long list in a short box. */
export const Vertical = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByText('v3.30.0')).toBeVisible();
    },
});

/** A wide row in a narrow box. */
export const Horizontal = meta.story({
    render: () => (
        <ScrollArea className="w-96 rounded-md border whitespace-nowrap">
            <div className="flex w-max gap-4 p-4">
                {releases.map((release) => (
                    <div
                        key={release}
                        className="bg-muted rounded-md px-4 py-8 text-sm"
                    >
                        {release}
                    </div>
                ))}
            </div>
            <ScrollBar orientation="horizontal" />
        </ScrollArea>
    ),
});
