<script setup lang="ts">
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { computed } from 'vue';
import InputPassword from './InputPassword.vue';

const props = withDefaults(
    defineProps<{
        /** The form field's name, as the server validates it. Also the default `id` and test id. */
        name: string;
        /** The input type. `password` adds a show/hide toggle. */
        type?: string;
        /** The visible label. Leave it out only when the field is labelled some other way. */
        label?: string;
        /** An example value, not a substitute for the label. */
        placeholder?: string;
        /** The input's id; defaults to `name`. Set it when one name appears twice on a page. */
        id?: string;
        /** The `data-testid` for end-to-end tests; defaults to `name`. */
        testId?: string;
        /** The field's validation error, from its own form: `<Form v-slot="{ errors }">` or useForm(). */
        error?: string;
    }>(),
    {
        type: 'text',
    },
);

defineOptions({ inheritAttrs: false });

const isInvalid = computed(() => !!props.error);

// IDs for accessibility (canonical id prefers provided id, falls back to name)
const id = computed(() => props.id || props.name);
const labelId = computed(() => `${id.value}-label`);
const errorId = computed(() => `${id.value}-error`);
const describedBy = computed(() =>
    isInvalid.value ? errorId.value : undefined,
);

// Test IDs (fallback to name to avoid undefined attributes)
const testIdComputed = computed(() => props.testId || props.name);
const errorTestId = computed(() => `${testIdComputed.value}-error`);

// Dynamic component selection
const component = computed(() =>
    props.type === 'password' ? InputPassword : Input,
);

// Type prop handling (only pass to Input, not InputPassword)
const typeAttribute = computed(() =>
    props.type !== 'password' ? props.type : undefined,
);
</script>

<template>
    <Field :data-invalid="isInvalid">
        <FieldLabel v-if="label" :id="labelId" :for="id">
            {{ label }}
        </FieldLabel>
        <component
            :is="component"
            :id="id"
            :name="name"
            :type="typeAttribute"
            :data-testid="testIdComputed"
            :placeholder="placeholder"
            :aria-invalid="isInvalid"
            :aria-labelledby="labelId"
            :aria-describedby="describedBy"
            v-bind="$attrs"
        />
        <FieldError
            v-if="isInvalid"
            :id="errorId"
            :data-testid="errorTestId"
            aria-live="polite"
        >
            {{ error }}
        </FieldError>
    </Field>
</template>
