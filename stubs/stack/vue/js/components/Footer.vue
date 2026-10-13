<script setup lang="ts">
import { useSettings } from '@saucebase/vue/composables/useSettings';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import IconHeart from '~icons/heroicons/heart';

const settings = useSettings();

const name = computed(() => settings.value.general.site_name);

// Sized by letter count so the name stays on one line, and sunk a little below the edge.
const nameStyle = computed(() => ({
    fontSize: `min(22vw, 420px, calc((100vw - 20px) * ${2 / (name.value.length + 0.2)}))`,
    transform: `translateY(${/[gjpqy]/.test(name.value) ? -0.05 : 0.2}em)`,
}));
</script>

<template>
    <footer class="relative mt-auto w-full overflow-hidden pb-12 sm:pb-48">
        <div
            class="relative z-10 mx-4 py-8 min-[450px]:mx-auto min-[450px]:max-w-7xl min-[450px]:px-6"
        >
            <div
                class="text-muted-foreground flex w-full flex-col items-center justify-between gap-4 text-sm md:flex-row"
            >
                <div
                    class="flex flex-col items-center gap-y-1 md:flex-row md:items-center md:gap-x-6 md:gap-y-0"
                >
                    <span data-testid="footer-app-name">
                        © {{ new Date().getFullYear() }}
                        {{ settings.general.site_name }}
                    </span>
                    <a
                        href="https://github.com/saucebase-dev/saucebase"
                        class="hover:text-foreground"
                    >
                        {{ $t('GitHub') }}
                    </a>
                    <a
                        href="https://saucebase-dev.github.io/docs/"
                        class="hover:text-foreground"
                    >
                        {{ $t('Documentation') }}
                    </a>
                    <Link
                        :href="route('privacy')"
                        class="hover:text-foreground"
                    >
                        {{ $t('Privacy') }}
                    </Link>
                    <Link :href="route('terms')" class="hover:text-foreground">
                        {{ $t('Terms') }}
                    </Link>
                </div>
                <div class="flex items-center gap-1">
                    <span>{{ $t('Crafted with') }}</span>
                    <IconHeart class="h-4 w-4 text-red-500" />
                    <span>{{ $t('for developers') }}</span>
                    <span>{{ $t('by :name', { name: 'Renan Roble' }) }}</span>
                </div>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-2.5 z-0 w-full font-mono">
            <p
                class="text-center leading-none font-black -tracking-widest whitespace-nowrap select-none"
                :style="nameStyle"
                data-testid="footer-watermark"
            >
                <span class="text-foreground/10 inline-block pr-[0.1em]">
                    {{ name }}
                </span>
            </p>
        </div>
    </footer>
</template>
