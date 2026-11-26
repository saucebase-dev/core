import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import { Slider } from '.';

/**
 * Picks a number in a range by dragging, where the exact value matters less than roughly
 * where: volume, a price filter. Arrow keys move it one `step`. When the exact number
 * matters, use UI/NumberField.
 */
const meta = preview.meta({
    title: 'UI/Slider',
    component: Slider,
    args: { defaultValue: [40], max: 100, step: 1 },
    decorators: [
        () => ({ template: '<div class="max-w-sm py-4"><story /></div>' }),
    ],
});
export default meta;

/** One value. The arrow keys move it. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        const thumb = canvas.getByRole('slider');
        await expect(thumb).toHaveAttribute('aria-valuenow', '40');

        thumb.focus();
        await userEvent.keyboard('{ArrowRight}');

        await expect(thumb).toHaveAttribute('aria-valuenow', '41');
    },
});

/** Two thumbs: a range, such as a price filter. */
export const Range = meta.story({
    args: { defaultValue: [20, 80] },
    play: async ({ canvas }) => {
        await expect(canvas.getAllByRole('slider')).toHaveLength(2);
    },
});

/** In steps of 10. */
export const Stepped = meta.story({ args: { step: 10, defaultValue: [50] } });

/** Can't be moved. */
export const Disabled = meta.story({ args: { disabled: true } });
