import preview from '@/.storybook/preview';
import { expect } from 'storybook/test';
import InputField from './InputField.vue';

/**
 * A labelled text input with its validation error, for forms. Screen readers hear the
 * label and, when there is one, the error.
 *
 * The form passes the error in: `<Form v-slot="{ errors }">` or `useForm()`. The field
 * never reads the page, so it works in any form.
 */
const meta = preview.meta({
    title: 'UI/InputField',
    component: InputField,
    args: { name: 'email', label: 'Email', placeholder: 'you@example.com' },
});
export default meta;

/** Before the form is sent. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(canvas.queryByTestId('email-error')).toBeNull();
    },
});

/** After the server rejects the value: the message shows under the input, which is marked invalid. */
export const WithError = meta.story({
    args: { error: 'The email field is required.' },
    play: async ({ canvas }) => {
        await expect(canvas.getByTestId('email-error')).toHaveTextContent(
            'The email field is required.',
        );
        await expect(canvas.getByLabelText('Email')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
    },
});
