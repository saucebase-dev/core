import preview from '@/.storybook/preview';
import { Button } from '@/components/ui/button';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { expect } from 'storybook/test';
import CardLayout from './CardLayout';

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
        children: (
            <form className="grid gap-4" onSubmit={(e) => e.preventDefault()}>
                <Field>
                    <FieldLabel htmlFor="email">Email</FieldLabel>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        placeholder="you@example.com"
                    />
                </Field>
                <Button type="submit" className="w-full">
                    Email me a reset link
                </Button>
            </form>
        ),
    },
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
