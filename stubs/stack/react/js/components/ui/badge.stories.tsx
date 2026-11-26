import preview from '@/.storybook/preview';
import { Check } from 'lucide-react';
import { expect } from 'storybook/test';
import { Badge } from './badge';

/**
 * A short label beside something: a status, a count, a plan. Not a button; with
 * `asChild` around a link it becomes one and gets a hover.
 */
const meta = preview.meta({
    title: 'UI/Badge',
    component: Badge,
    args: { children: 'Pro' },
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
    args: { variant: 'secondary', children: 'Draft' },
});

/** Something wrong: overdue, failed. */
export const Destructive = meta.story({
    args: { variant: 'destructive', children: 'Overdue' },
});

/** Only an edge, in dense lists. */
export const Outline = meta.story({
    args: { variant: 'outline', children: 'Beta' },
});

/** With an icon before the label. */
export const WithIcon = meta.story({
    render: () => (
        <Badge variant="secondary">
            <Check />
            Verified
        </Badge>
    ),
});

/** As a link, with a hover. */
export const AsLink = meta.story({
    args: { asChild: true, children: <a href="#">Changelog</a> },
});
