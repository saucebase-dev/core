<script setup lang="ts">
import type { ComboboxInputEmits, ComboboxInputProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { SearchIcon } from '@lucide/vue';
import { reactiveOmit } from '@vueuse/core';
import { cn } from '@/lib/utils';
import { ComboboxInput, useForwardPropsEmits } from 'reka-ui';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<
    ComboboxInputProps & {
        class?: HTMLAttributes['class'];
    }
>();

const emits = defineEmits<ComboboxInputEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <div
        data-slot="command-input-wrapper"
        class="flex h-9 items-center gap-2 border-b px-3"
    >
        <SearchIcon class="size-4 shrink-0 opacity-50" />
        <ComboboxInput
            data-slot="command-input"
            :class="
                cn(
                    // The forms plugin borders and rings every input; this one sits in the list's own frame.
                    'placeholder:text-muted-foreground flex h-10 w-full rounded-md border-0 bg-transparent px-0 py-3 text-sm shadow-none outline-hidden focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50',
                    props.class,
                )
            "
            v-bind="{ ...$attrs, ...forwarded }"
        >
            <slot />
        </ComboboxInput>
    </div>
</template>
