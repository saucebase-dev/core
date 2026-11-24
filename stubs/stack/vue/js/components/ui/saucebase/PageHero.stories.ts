import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { expect } from 'storybook/test';
import IconNewspaper from '~icons/lucide/newspaper';
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
        icon: IconNewspaper,
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
    render: (args) => ({
        components: { Button, PageHero },
        setup: () => ({ args }),
        template: `
            <PageHero v-bind="args">
                <template #actions><Button>Subscribe</Button></template>
            </PageHero>`,
    }),
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
