import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { ChevronsUpDown } from 'lucide-react';
import { expect } from 'storybook/test';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from './collapsible';

/**
 * Shows and hides a section on demand: advanced options, older items. The trigger says
 * whether it's open (`aria-expanded`). For several sections where one opens at a time,
 * that's an accordion.
 */
const meta = preview.meta({
    title: 'UI/Collapsible',
    component: Collapsible,
    render: (args) => (
        <Collapsible {...args} className="grid max-w-sm gap-2">
            <div className="flex items-center justify-between">
                <span className="text-sm font-medium">3 older invoices</span>
                <CollapsibleTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Show older invoices"
                    >
                        <ChevronsUpDown />
                    </Button>
                </CollapsibleTrigger>
            </div>
            <div className="rounded-md border px-4 py-2 text-sm">
                INV-0042 · $29.00
            </div>
            <CollapsibleContent className="grid gap-2">
                <div className="rounded-md border px-4 py-2 text-sm">
                    INV-0041 · $29.00
                </div>
                <div className="rounded-md border px-4 py-2 text-sm">
                    INV-0040 · $29.00
                </div>
                <div className="rounded-md border px-4 py-2 text-sm">
                    INV-0039 · $19.00
                </div>
            </CollapsibleContent>
        </Collapsible>
    ),
});
export default meta;

/** Closed. The trigger opens it. */
export const Closed = meta.story({
    play: async ({ canvas, userEvent }) => {
        const trigger = canvas.getByRole('button', {
            name: 'Show older invoices',
        });
        await expect(canvas.queryByText(/INV-0041/)).toBeNull();

        await userEvent.click(trigger);

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(canvas.getByText(/INV-0041/)).toBeVisible();
    },
});

/** Open from the start. */
export const Open = meta.story({ args: { defaultOpen: true } });

/** Can't be toggled. */
export const Disabled = meta.story({ args: { disabled: true } });
