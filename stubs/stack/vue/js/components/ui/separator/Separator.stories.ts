import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import { Separator } from '.';

/**
 * A thin line between groups of content. Decorative by default, so screen readers skip
 * it; set `decorative: false` when it separates sections that matter.
 */
const meta = preview.meta({
    title: 'UI/Separator',
    component: Separator,
    render: (args) => ({
        components: { Separator },
        setup: () => ({ args }),
        template: `
            <div class="max-w-sm">
                <p class="text-sm font-medium">Billing</p>
                <p class="text-muted-foreground text-sm">Plan, invoices and payment method.</p>
                <Separator v-bind="args" class="my-4" />
                <p class="text-sm font-medium">Danger zone</p>
            </div>`,
    }),
});
export default meta;

/** Between stacked sections. */
export const Horizontal = meta.story({});

/** Between items in a row. */
export const Vertical = meta.story({
    args: { orientation: 'vertical' },
    render: (args) => ({
        components: { Separator },
        setup: () => ({ args }),
        template: `
            <div class="flex h-5 items-center gap-4 text-sm">
                <span>Docs</span>
                <Separator v-bind="args" />
                <span>Changelog</span>
                <Separator v-bind="args" />
                <span>Status</span>
            </div>`,
    }),
});

/** Not decorative: screen readers hear a separator. */
export const Semantic = meta.story({
    args: { decorative: false },
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('separator')).toBeInTheDocument();
    },
});
