import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { expect } from 'storybook/test';
import IconChevrons from '~icons/lucide/chevrons-up-down';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '.';

/**
 * Shows and hides a section on demand: advanced options, older items. The trigger says
 * whether it's open (`aria-expanded`). For several sections where one opens at a time,
 * that's an accordion.
 */
const meta = preview.meta({
    title: 'UI/Collapsible',
    component: Collapsible,
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        default: { control: false },
    },
    render: (args) => ({
        components: {
            Button,
            Collapsible,
            CollapsibleContent,
            CollapsibleTrigger,
            IconChevrons,
        },
        setup: () => ({ args }),
        template: `
            <Collapsible v-bind="args" class="grid max-w-sm gap-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">3 older invoices</span>
                    <CollapsibleTrigger as-child>
                        <Button variant="ghost" size="icon-sm" aria-label="Show older invoices"><IconChevrons /></Button>
                    </CollapsibleTrigger>
                </div>
                <div class="rounded-md border px-4 py-2 text-sm">INV-0042 · $29.00</div>
                <CollapsibleContent class="grid gap-2">
                    <div class="rounded-md border px-4 py-2 text-sm">INV-0041 · $29.00</div>
                    <div class="rounded-md border px-4 py-2 text-sm">INV-0040 · $29.00</div>
                    <div class="rounded-md border px-4 py-2 text-sm">INV-0039 · $19.00</div>
                </CollapsibleContent>
            </Collapsible>`,
    }),
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
