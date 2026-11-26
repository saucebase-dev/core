import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { expect } from 'storybook/test';
import IconKey from '~icons/lucide/key-round';
import { EmptyState } from '.';

/**
 * What a list shows before it has anything: an icon, a title, a line on why it's empty,
 * and the action that fills it. Other sizes or an illustration: compose
 * `@/components/ui/empty` instead.
 */
const meta = preview.meta({
    title: 'UI/EmptyState',
    component: EmptyState,
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        icon: { control: false },
        default: { control: false },
    },
    args: {
        title: 'No API tokens yet',
        description: 'Create a token to call the API from your own scripts.',
    },
    render: (args) => ({
        components: { Button, EmptyState, IconKey },
        setup: () => ({ args }),
        template: `
            <EmptyState v-bind="args">
                <template #icon><IconKey /></template>
                <Button>Create token</Button>
            </EmptyState>`,
    }),
});
export default meta;

/** Icon, title, line and action. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByText('No API tokens yet')).toBeVisible();
        await expect(
            canvas.getByRole('button', { name: 'Create token' }),
        ).toBeVisible();
    },
});

/** Only a title, where the reason is obvious and nothing can fill it here. */
export const TitleOnly = meta.story({
    args: { title: 'No invoices yet', description: undefined },
    render: (args) => ({
        components: { EmptyState },
        setup: () => ({ args }),
        template: '<EmptyState v-bind="args" />',
    }),
});
