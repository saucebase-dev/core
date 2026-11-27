import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import { Avatar, AvatarFallback, AvatarImage } from '.';

// The picture's URL, and the root's classes.
type Args = { src: string; class?: string };

/**
 * A person's picture, round. Until the image loads, or when there is none or it fails,
 * the fallback shows: their initials.
 */
const meta = preview.meta({
    title: 'UI/Avatar',
    component: Avatar,
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        default: { control: false },
    },
    args: { src: '/images/icon-on-light.svg' },
    render: (args: Args) => ({
        components: { Avatar, AvatarFallback, AvatarImage },
        setup: () => ({ args }),
        template: `
            <Avatar :class="args.class">
                <AvatarImage :src="args.src" alt="Ada Lovelace" />
                <AvatarFallback>AL</AvatarFallback>
            </Avatar>`,
    }),
});
export default meta;

/** With a picture. */
export const WithImage = meta.story({
    play: async ({ canvas }) => {
        await expect(
            await canvas.findByRole('img', { name: 'Ada Lovelace' }),
        ).toBeVisible();
    },
});

/** No picture, or it failed to load: the initials. */
export const Fallback = meta.story({
    args: { src: '/images/missing.png' },
    play: async ({ canvas }) => {
        await expect(await canvas.findByText('AL')).toBeVisible();
    },
});

/** Larger, on a profile page. */
export const Large = meta.story({
    args: { src: '/images/missing.png', class: 'size-16 text-lg' },
});
