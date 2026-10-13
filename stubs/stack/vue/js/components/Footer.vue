<script setup lang="ts">
import { useSettings } from '@saucebase/vue/composables/useSettings';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import IconHeart from '~icons/heroicons/heart';

const settings = useSettings();

const name = computed(() => settings.value.general.site_name);

// A monospace letter is 0.6em wide, so this is the name's natural width at the
// font size of 100 the drawing uses.
const width = computed(() => name.value.length * 60);
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
        <!--
            Drawn as SVG so the name always spans the width it is given: `textLength`
            stretches or squeezes it to fit, whatever its length, the font or the
            window. The name is already in the footer as text, so this copy is hidden
            from assistive technology.
        -->
        <div class="absolute inset-x-0 bottom-0 z-0 px-4" aria-hidden="true">
            <svg
                v-if="name"
                :viewBox="`0 0 ${width} 104`"
                :style="{ maxWidth: `${name.length * 140}px` }"
                class="text-foreground/10 mx-auto block w-full font-mono font-black select-none"
                data-testid="footer-watermark"
            >
                <text
                    y="80"
                    font-size="100"
                    :textLength="width"
                    lengthAdjust="spacingAndGlyphs"
                    fill="currentColor"
                >
                    {{ name }}
                </text>
            </svg>
        </div>
    </footer>
</template>
