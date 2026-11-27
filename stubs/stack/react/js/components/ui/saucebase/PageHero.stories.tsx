import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { MapIcon, Newspaper, Rocket } from 'lucide-react';
import { expect } from 'storybook/test';
import { PageHero } from '.';

/**
 * The banner at the top of a public page: an icon, the page's title, a line about it,
 * and actions on the right. The gradient comes from the primary colour, so it follows
 * the theme.
 *
 * Set `width` to the width of the content below it, so their edges line up.
 */
const meta = preview.meta({
    title: 'UI/PageHero',
    component: PageHero,
    parameters: { layout: 'fullscreen' },
    args: {
        title: 'Blog',
        description: 'Product news, guides and the occasional deep dive.',
        // Its name in `mapping` below, which Storybook swaps for the component.
        icon: 'Newspaper' as never,
    },
    argTypes: {
        // A component can't go through the URL: pick one by name.
        icon: {
            options: ['Newspaper', 'Rocket', 'Map'],
            mapping: { Newspaper, Rocket, Map: MapIcon },
            control: 'select',
        },
        // The story fills it itself: its control would do nothing.
        actions: { control: false },
    },
});
export default meta;

/** Icon, title and description. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('heading', { level: 1, name: 'Blog' }),
        ).toBeVisible();
    },
});

/** With an action on the right. */
export const WithActions = meta.story({
    args: { actions: <Button>Subscribe</Button> },
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('button', { name: 'Subscribe' }),
        ).toBeVisible();
    },
});

/** Narrow, over a single column of text. */
export const Narrow = meta.story({
    args: { width: '3xl', icon: undefined },
});
