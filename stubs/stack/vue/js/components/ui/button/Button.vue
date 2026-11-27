<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import type { ButtonVariants } from '.';
import { Primitive } from 'reka-ui';
import { cn } from '@/lib/utils';
import { buttonVariants } from '.';

interface Props extends PrimitiveProps {
    /** How much the action stands out: `default` for the page's main action, `destructive` for one that deletes or can't be undone. */
    variant?: NonNullable<ButtonVariants['variant']>;
    /** `sm` in dense rows and toolbars, `lg` for a hero or a lone call to action. */
    size?: NonNullable<ButtonVariants['size']>;
    /** Extra classes, merged over the variant's. */
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    as: 'button',
});
</script>

<template>
    <Primitive
        data-slot="button"
        :data-variant="variant"
        :data-size="size"
        :as="as"
        :as-child="asChild"
        :class="cn(buttonVariants({ variant, size }), props.class)"
    >
        <slot />
    </Primitive>
</template>
