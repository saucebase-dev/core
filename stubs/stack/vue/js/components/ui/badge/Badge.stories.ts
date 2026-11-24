import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import IconCheck from '~icons/lucide/check';
import { Badge } from '.';

/**
 * A short label beside something: a status, a count, a plan. Not a button; with
 * `as="a"` it becomes a link and gets a hover.
 */
const meta = preview.meta({
    title: 'UI/Badge',
    component: Badge,
    args: { default: 'Pro' },
    // The variants' types allow null, so Storybook can't offer them as a list itself.
    argTypes: {
        variant: {
            control: 'select',
            options: ['default', 'secondary', 'destructive', 'outline'],
        },
    },
});
export default meta;

/** The brand colour: what stands out, such as the current plan. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByText('Pro')).toBeVisible();
    },
});

/** Quieter: a neutral status. */
export const Secondary = meta.story({
    args: { variant: 'secondary', default: 'Draft' },
});

/** Something wrong: overdue, failed. */
export const Destructive = meta.story({
    args: { variant: 'destructive', default: 'Overdue' },
});

/** Only an edge, in dense lists. */
export const Outline = meta.story({
    args: { variant: 'outline', default: 'Beta' },
});

/** With an icon before the label. */
export const WithIcon = meta.story({
    render: (args) => ({
        components: { Badge, IconCheck },
        setup: () => ({ args }),
        template: '<Badge variant="secondary"><IconCheck />Verified</Badge>',
    }),
});

/** As a link, with a hover. */
export const AsLink = meta.story({
    args: { as: 'a', href: '#', default: 'Changelog' },
});
