import preview from '@/.storybook/preview';
import { Label } from '@/components/ui/label';
import { expect } from 'storybook/test';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '.';

const components = {
    Label,
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
};

/**
 * A number the user types or steps with − and +: seats on a plan, a quantity. `min`,
 * `max` and `step` bound it, and a button turns off at its bound. Arrow keys step it too.
 */
const meta = preview.meta({
    title: 'UI/NumberField',
    component: NumberField,
    args: { id: 'seats', defaultValue: 5, min: 1, max: 50 },
    decorators: [() => ({ template: '<div class="max-w-48"><story /></div>' })],
    render: (args) => ({
        components,
        setup: () => ({ args }),
        template: `
            <NumberField v-bind="args">
                <Label for="seats">Seats</Label>
                <NumberFieldContent>
                    <NumberFieldDecrement />
                    <NumberFieldInput />
                    <NumberFieldIncrement />
                </NumberFieldContent>
            </NumberField>`,
    }),
});
export default meta;

/** Within its bounds. + adds one. */
export const Default = meta.story({
    play: async ({ canvas, userEvent }) => {
        const input = canvas.getByRole('spinbutton', { name: 'Seats' });

        await userEvent.click(
            canvas.getByRole('button', { name: /increase/i }),
        );

        await expect(input).toHaveValue('6');
    },
});

/** At its minimum: − is off. */
export const AtMinimum = meta.story({
    args: { defaultValue: 1 },
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('button', { name: /decrease/i }),
        ).toBeDisabled();
    },
});

/** As money, formatted for the locale. */
export const Currency = meta.story({
    args: {
        defaultValue: 29,
        min: 0,
        step: 0.5,
        formatOptions: { style: 'currency', currency: 'USD' },
    },
});

/** Can't be changed. */
export const Disabled = meta.story({ args: { disabled: true } });
