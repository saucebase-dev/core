import vue from '@vitejs/plugin-vue';
import i18n from 'laravel-vue-i18n/vite';
import Icons from 'unplugin-icons/vite';
import type { PluginOption } from 'vite';
import { collectModuleLangPaths } from '../modules.ts';
import { saucebase as core } from '../vite.ts';

/**
 * The Saucebase build for a Vue app: `plugins: [saucebase()]`.
 */
export async function saucebase(): Promise<PluginOption[]> {
    return [
        core({
            framework: 'vue',
            entry: 'resources/js/app.ts',
            requires: {
                vue: '3.5.0',
                '@inertiajs/vue3': '3.0.0',
                '@inertiaui/modal-vue': '3.0.0',
                'laravel-vue-i18n': '2.0.0',
                'vue-sonner': '2.0.0',
            },
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        Icons({ compiler: 'vue3' }),
        i18n({ additionalLangPaths: await collectModuleLangPaths() }),
    ];
}
