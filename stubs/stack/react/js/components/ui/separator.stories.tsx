import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import { Separator } from './separator';

/**
 * A thin line between groups of content. Decorative by default, so screen readers skip
 * it; set `decorative: false` when it separates sections that matter.
 */
const meta = preview.meta({
    title: 'UI/Separator',
    component: Separator,
    render: (args) => (
        <div className="max-w-sm">
            <p className="text-sm font-medium">Billing</p>
            <p className="text-muted-foreground text-sm">
                Plan, invoices and payment method.
            </p>
            <Separator {...args} className="my-4" />
            <p className="text-sm font-medium">Danger zone</p>
        </div>
    ),
});
export default meta;

/** Between stacked sections. */
export const Horizontal = meta.story({});

/** Between items in a row. */
export const Vertical = meta.story({
    args: { orientation: 'vertical' },
    render: (args) => (
        <div className="flex h-5 items-center gap-4 text-sm">
            <span>Docs</span>
            <Separator {...args} />
            <span>Changelog</span>
            <Separator {...args} />
            <span>Status</span>
        </div>
    ),
});

/** Not decorative: screen readers hear a separator. */
export const Semantic = meta.story({
    args: { decorative: false },
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('separator')).toBeInTheDocument();
    },
});
