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
} from './field';

/**
 * Lays out one form control with its label, description and error, and groups fields
 * into a form. `Field` marks itself invalid with `data-invalid`, which turns its label
 * red; the control still needs `aria-invalid`.
 */
const meta = preview.meta({
    title: 'UI/Field',
    component: Field,
    decorators: [
        (Story) => (
            <div className="max-w-md">
                <Story />
            </div>
        ),
    ],
    render: (args) => (
        <Field {...args}>
            <FieldLabel htmlFor="name">Workspace name</FieldLabel>
            <Input id="name" placeholder="Acme Inc." />
            <FieldDescription>Shown to everyone you invite.</FieldDescription>
        </Field>
    ),
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
    render: () => (
        <Field data-invalid="true">
            <FieldLabel htmlFor="slug">Workspace URL</FieldLabel>
            <Input
                id="slug"
                defaultValue="acme"
                aria-invalid="true"
                aria-describedby="slug-error"
            />
            <FieldError id="slug-error">This URL is already taken.</FieldError>
        </Field>
    ),
    play: async ({ canvas }) => {
        await expect(canvas.getByRole('alert')).toHaveTextContent(
            'This URL is already taken.',
        );
    },
});

/** Errors from a validator as a list: duplicates are shown once. */
export const SeveralErrors = meta.story({
    render: () => (
        <Field data-invalid="true">
            <FieldLabel htmlFor="password">Password</FieldLabel>
            <Input id="password" type="password" aria-invalid="true" />
            <FieldError
                errors={[
                    { message: 'At least 12 characters.' },
                    { message: 'At least one number.' },
                    { message: 'At least 12 characters.' },
                ]}
            />
        </Field>
    ),
    play: async ({ canvas }) => {
        await expect(canvas.getAllByRole('listitem')).toHaveLength(2);
    },
});

/** A checkbox beside its label and description. */
export const Horizontal = meta.story({
    render: () => (
        <Field orientation="horizontal">
            <Checkbox id="digest" />
            <FieldContent>
                <FieldLabel htmlFor="digest">Weekly digest</FieldLabel>
                <FieldDescription>
                    A summary of your workspace's activity, every Monday.
                </FieldDescription>
            </FieldContent>
        </Field>
    ),
});

/** A whole form section: a legend, grouped fields and a separator. */
export const Form = meta.story({
    render: () => (
        <FieldSet>
            <FieldLegend>Billing address</FieldLegend>
            <FieldDescription>Printed on your invoices.</FieldDescription>
            <FieldGroup>
                <Field>
                    <FieldLabel htmlFor="company">Company</FieldLabel>
                    <Input id="company" placeholder="Acme Inc." />
                </Field>
                <Field>
                    <FieldLabel htmlFor="vat">VAT number</FieldLabel>
                    <Input id="vat" placeholder="GB123456789" />
                </Field>
                <FieldSeparator>Optional</FieldSeparator>
                <Field>
                    <FieldLabel htmlFor="notes">
                        Notes for your accountant
                    </FieldLabel>
                    <Textarea id="notes" />
                </Field>
            </FieldGroup>
        </FieldSet>
    ),
    play: async ({ canvas }) => {
        await expect(
            canvas.getByRole('group', { name: 'Billing address' }),
        ).toBeVisible();
    },
});
