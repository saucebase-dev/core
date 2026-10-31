<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center gap-2 rounded-lg border border-dashed px-6 py-10 text-center"
    >
        <div
            v-if="$slots.icon"
            class="bg-muted text-muted-foreground flex size-10 items-center justify-center rounded-full [&>svg]:size-5"
        >
            <slot name="icon" />
        </div>
        <p class="font-medium">{{ title }}</p>
        <p v-if="description" class="text-muted-foreground text-sm">
            {{ description }}
        </p>
        <div v-if="$slots.default" class="mt-2">
            <slot />
        </div>
    </div>
</template>
