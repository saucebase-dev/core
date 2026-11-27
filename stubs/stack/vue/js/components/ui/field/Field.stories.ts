import preview from '@/.storybook/preview';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { expect } from 'storybook/test';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
} from '.';

const components = {
    Checkbox,
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
    Input,
    Textarea,
};

/**
 * Lays out one form control with its label, description and error, and groups fields
 * into a form. `Field` marks itself invalid with `data-invalid`, which turns its label
 * red; the control still needs `aria-invalid`.
 *
 * For a plain text input, UI/InputField already does all of this.
 */
const meta = preview.meta({
    title: 'UI/Field',
    component: Field,
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        default: { control: false },
    },
    decorators: [() => ({ template: '<div class="max-w-md"><story /></div>' })],
    render: (args) => ({
        components,
        setup: () => ({ args }),
        template: `
            <Field v-bind="args">
                <FieldLabel for="name">Workspace name</FieldLabel>
                <Input id="name" placeholder="Acme Inc." />
                <FieldDescription>Shown to everyone you invite.</FieldDescription>
            </Field>`,
    }),
});
export default meta;

/** Label, control and a line of help below. */
export const Default = meta.story({
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('textbox', { name: 'Workspace name' }),
        ).toBeVisible();
    },
});

/** Rejected by the server: the label turns red and the error shows below. */
export const WithError = meta.story({
    render: () => ({
        components,
        template: `
            <Field data-invalid="true">
                <FieldLabel for="slug">Workspace URL</FieldLabel>
                <Input id="slug" default-value="acme" aria-invalid="true" aria-describedby="slug-error" />
                <FieldError id="slug-error">This URL is already taken.</FieldError>
            </Field>`,
    }),
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('alert')).toHaveTextContent(
            'This URL is already taken.',
        );
    },
});

/** Errors from a validator as a list: duplicates are shown once. */
export const SeveralErrors = meta.story({
    render: () => ({
        components,
        setup: () => ({
            errors: [
                'At least 12 characters.',
                'At least one number.',
                'At least 12 characters.',
            ],
        }),
        template: `
            <Field data-invalid="true">
                <FieldLabel for="password">Password</FieldLabel>
                <Input id="password" type="password" aria-invalid="true" />
                <FieldError :errors="errors" />
            </Field>`,
    }),
    play: async ({ canvas }) => {
        await expect(canvas.getAllByRole('listitem')).toHaveLength(2);
    },
});

/** A checkbox beside its label and description. */
export const Horizontal = meta.story({
    render: () => ({
        components,
        template: `
            <Field orientation="horizontal">
                <Checkbox id="digest" />
                <FieldContent>
                    <FieldLabel for="digest">Weekly digest</FieldLabel>
                    <FieldDescription>A summary of your workspace's activity, every Monday.</FieldDescription>
                </FieldContent>
            </Field>`,
    }),
});

/** A whole form section: a legend, grouped fields and a separator. */
export const Form = meta.story({
    render: () => ({
        components,
        template: `
            <FieldSet>
                <FieldLegend>Billing address</FieldLegend>
                <FieldDescription>Printed on your invoices.</FieldDescription>
                <FieldGroup>
                    <Field>
                        <FieldLabel for="company">Company</FieldLabel>
                        <Input id="company" placeholder="Acme Inc." />
                    </Field>
                    <Field>
                        <FieldLabel for="vat">VAT number</FieldLabel>
                        <Input id="vat" placeholder="GB123456789" />
                    </Field>
                    <FieldSeparator>Optional</FieldSeparator>
                    <Field>
                        <FieldLabel for="notes">Notes for your accountant</FieldLabel>
                        <Textarea id="notes" />
                    </Field>
                </FieldGroup>
            </FieldSet>`,
    }),
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('group', { name: 'Billing address' }),
        ).toBeVisible();
    },
});
