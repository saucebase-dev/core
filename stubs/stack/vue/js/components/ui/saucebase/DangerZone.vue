<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TriangleAlert } from '@lucide/vue';

withDefaults(defineProps<{ title?: string; showHeader?: boolean }>(), {
    showHeader: true,
});
</script>

<template>
    <Card class="border-destructive/50" data-testid="danger-zone">
        <CardHeader v-if="showHeader">
            <CardTitle class="text-destructive flex items-center gap-2">
                <TriangleAlert class="size-4" />
                {{ title ?? $t('Danger zone') }}
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
            <slot />
        </CardContent>
    </Card>
</template>
