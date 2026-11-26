import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { InputPassword } from '@/components/ui/input-password';
import type { ComponentProps } from 'react';

type InputFieldProps = Omit<ComponentProps<'input'>, 'id' | 'name'> & {
    /** The form field's name, as the server validates it. Also the default `id` and test id. */
    name: string;
    /** The input type. `password` adds a show/hide toggle. */
    type?: string;
    /** The visible label. Leave it out only when the field is labelled some other way. */
    label?: string;
    /** The input's id; defaults to `name`. Set it when one name appears twice on a page. */
    id?: string;
    /** The `data-testid` for end-to-end tests; defaults to `name`. */
    testId?: string;
    /** The field's validation error, from its own form: `<Form>`'s render props or useForm(). */
    error?: string;
};

/** A labelled input with its validation error, for forms. */
export function InputField({
    name,
    type = 'text',
    label,
    id = name,
    testId = name,
    error,
    ...props
}: InputFieldProps) {
    const labelId = `${id}-label`;
    const errorId = `${id}-error`;
    const inputProps = {
        ...props,
        id,
        name,
        'data-testid': testId,
        'aria-invalid': !!error,
        'aria-labelledby': label ? labelId : undefined,
        'aria-describedby': error ? errorId : undefined,
    };

    return (
        <Field data-invalid={!!error}>
            {label && (
                <FieldLabel id={labelId} htmlFor={id}>
                    {label}
                </FieldLabel>
            )}
            {type === 'password' ? (
                <InputPassword {...inputProps} />
            ) : (
                <Input {...inputProps} type={type} />
            )}
            {error && (
                <FieldError
                    id={errorId}
                    data-testid={`${testId}-error`}
                    aria-live="polite"
                >
                    {error}
                </FieldError>
            )}
        </Field>
    );
}
