import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { KeyRound } from 'lucide-react';
import { expect } from 'storybook/test';
import { EmptyState } from '.';

/**
 * What a list shows before it has anything: an icon, a title, a line on why it's empty,
 * and the action that fills it. Other sizes or an illustration: compose
 * `@/components/ui/empty` instead.
 */
const meta = preview.meta({
    title: 'UI/EmptyState',
    component: EmptyState,
    args: {
        title: 'No API tokens yet',
        description: 'Create a token to call the API from your own scripts.',
        icon: <KeyRound />,
        children: <Button>Create token</Button>,
    },
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
    args: {
        title: 'No invoices yet',
        description: undefined,
        icon: undefined,
        children: undefined,
    },
});
