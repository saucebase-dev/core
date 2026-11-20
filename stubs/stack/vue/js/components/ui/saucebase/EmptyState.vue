<script setup lang="ts">
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';

/**
 * The common empty state: an icon, a title, a line and an action. A `class` lands on the
 * container. Other sizes or an illustration: compose `@/components/ui/empty` instead.
 */
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <Empty class="flex-none gap-2 border px-6 py-10 md:px-6 md:py-10">
        <EmptyHeader class="max-w-none">
            <EmptyMedia
                v-if="$slots.icon"
                variant="icon"
                class="text-muted-foreground mb-0 rounded-full [&_svg:not([class*='size-'])]:size-5"
            >
                <slot name="icon" />
            </EmptyMedia>
            <EmptyTitle class="text-base tracking-normal">{{
                title
            }}</EmptyTitle>
            <EmptyDescription v-if="description">
                {{ description }}
            </EmptyDescription>
        </EmptyHeader>
        <EmptyContent v-if="$slots.default" class="mt-2">
            <slot />
        </EmptyContent>
    </Empty>
</template>
