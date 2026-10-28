<script setup lang="ts">
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Check, Copy } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';
import type { HTMLAttributes } from 'vue';
import { onUnmounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
    value: string;
    testId: string;
    class?: HTMLAttributes['class'];
}>();

// The tick is feedback the user can see without reading the toast, so it clears itself
// on a timer rather than waiting for something else to re-render.
const copied = ref(false);
let copyTimer: ReturnType<typeof setTimeout> | null = null;

function copy() {
    navigator.clipboard
        .writeText(props.value)
        .then(() => {
            toast.success(trans('Copied to clipboard'));
            if (copyTimer) clearTimeout(copyTimer);
            copied.value = true;
            copyTimer = setTimeout(() => (copied.value = false), 2000);
        })
        .catch(() => toast.error(trans('Failed to copy')));
}

onUnmounted(() => {
    if (copyTimer) clearTimeout(copyTimer);
});
</script>

<template>
    <InputGroup :class="props.class">
        <!-- `model-value`, not `value`: Input binds v-model to the native input, so a
             plain value attribute is overwritten by the empty model on first render. -->
        <InputGroupInput
            readonly
            :model-value="props.value"
            :data-testid="props.testId"
            @focus="(e: FocusEvent) => (e.target as HTMLInputElement).select()"
        />
        <InputGroupAddon align="inline-end">
            <InputGroupButton
                type="button"
                size="icon-xs"
                :aria-label="$t('Copy')"
                :data-testid="`copy-${props.testId}`"
                @click="copy"
            >
                <Check v-if="copied" class="text-emerald-500" />
                <Copy v-else />
            </InputGroupButton>
        </InputGroupAddon>
    </InputGroup>
</template>
