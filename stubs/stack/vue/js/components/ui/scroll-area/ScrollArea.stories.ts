import preview from '@/.storybook/preview';
import { Separator } from '@/components/ui/separator';
import { expect } from 'storybook/test';
import { ScrollArea, ScrollBar } from '.';

const releases = Array.from({ length: 30 }, (_, i) => `v3.${30 - i}.0`);

/**
 * A fixed-size box whose content scrolls, with a thin scrollbar that matches the theme
 * on every platform. Give it a height (or width) and put the long content inside.
 */
const meta = preview.meta({
    title: 'UI/ScrollArea',
    component: ScrollArea,
    render: () => ({
        components: { ScrollArea, Separator },
        setup: () => ({ releases }),
        template: `
            <ScrollArea class="h-72 w-48 rounded-md border">
                <div class="p-4">
                    <h4 class="mb-4 text-sm font-medium">Releases</h4>
                    <template v-for="release in releases" :key="release">
                        <div class="text-sm">{{ release }}</div>
                        <Separator class="my-2" />
                    </template>
                </div>
            </ScrollArea>`,
    }),
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
    render: () => ({
        components: { ScrollArea, ScrollBar },
        setup: () => ({ releases }),
        template: `
            <ScrollArea class="w-96 rounded-md border whitespace-nowrap">
                <div class="flex w-max gap-4 p-4">
                    <div v-for="release in releases" :key="release" class="bg-muted rounded-md px-4 py-8 text-sm">{{ release }}</div>
                </div>
                <ScrollBar orientation="horizontal" />
            </ScrollArea>`,
    }),
});
