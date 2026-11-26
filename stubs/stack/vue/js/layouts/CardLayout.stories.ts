import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import InputField from '@/components/ui/input/InputField.vue';
import { expect } from 'storybook/test';
import CardLayout from './CardLayout.vue';

/**
 * A centred card on an otherwise empty page, for short self-contained steps: sign in,
 * register, a one-question form. Modules share it so these steps read as one flow.
 *
 * The page's `status` shows above the content as a success message, its `error` as an
 * error.
 */
const meta = preview.meta({
    title: 'Layout/CardLayout',
    component: CardLayout,
    parameters: {
        // Its stories differ only in page props, which one docs page shares.
        docs: { story: { inline: false, iframeHeight: '640px' } },
        layout: 'fullscreen',
    },
    args: {
        title: 'Forgot your password?',
        description: "Enter your email and we'll send you a reset link.",
    },
    render: (args) => ({
        components: { Button, CardLayout, InputField },
        setup: () => ({ args }),
        template: `
            <CardLayout v-bind="args">
                <form class="grid gap-4" @submit.prevent>
                    <InputField label="Email" name="email" type="email" placeholder="you@example.com" />
                    <Button type="submit" class="w-full">Email me a reset link</Button>
                </form>
            </CardLayout>`,
    }),
});
export default meta;

/** A form, before it's sent. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.getByText('Forgot your password?')).toBeVisible();
        await expect(canvas.queryByTestId('alert')).toBeNull();
    },
});

/** After it worked: the page's `status`. */
export const WithStatus = meta.story({
    parameters: {
        inertia: {
            status: 'We have emailed your password reset link.',
        },
    },
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('alert')).toHaveTextContent(
            'We have emailed your password reset link.',
        );
    },
});

/** After it failed: the page's `error`. */
export const WithError = meta.story({
    parameters: {
        inertia: { error: 'Too many attempts. Try again in a minute.' },
    },
});
