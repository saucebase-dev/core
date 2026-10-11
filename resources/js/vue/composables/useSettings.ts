import { usePage } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';
import type { Settings } from '../../lib/settings';

export function useSettings(): ComputedRef<Settings> {
    const page = usePage();

    return computed(() => page.props.settings);
}
