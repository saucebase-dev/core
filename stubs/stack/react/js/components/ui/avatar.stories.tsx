import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import { Avatar, AvatarFallback, AvatarImage } from './avatar';

// The picture's URL, and the root's classes.
type Args = { src: string; className?: string };

/**
 * A person's picture, round. Until the image loads, or when there is none or it fails,
 * the fallback shows: their initials.
 */
const meta = preview.meta({
    title: 'UI/Avatar',
    component: Avatar,
    args: { src: '/images/icon-on-light.svg' },
    render: (args: Args) => (
        <Avatar className={args.className}>
            <AvatarImage src={args.src} alt="Ada Lovelace" />
            <AvatarFallback>AL</AvatarFallback>
        </Avatar>
    ),
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
    args: { src: '/images/missing.png', className: 'size-16 text-lg' },
});
