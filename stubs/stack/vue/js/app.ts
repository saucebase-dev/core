import { setupProgress } from '@inertiajs/core';
import { createInertiaApp } from '@inertiajs/vue3';
import { putConfig, withInertiaModal } from '@inertiaui/modal-vue';
import { initializeModals } from '@saucebase/core/lib/modal';
import { siteTitle } from '@saucebase/core/lib/settings';
import { ziggyForServer } from '@saucebase/core/lib/ziggy';
import { resolveLanguage } from '@saucebase/vue/lib/language';
import {
    registerAppPages,
    resolveModularPageComponent,
} from '@saucebase/vue/lib/pages';
import { useColorMode } from '@vueuse/core';
import { i18nVue } from 'laravel-vue-i18n';
import type { App, DefineComponent } from 'vue';
import { route as ziggyRoute, ZiggyVue } from 'ziggy-js';
import { registerNavigationDefaults } from './lib/navigation';

import {
    discoverModuleSetups,
    executeAfterMountCallbacks,
    executeModuleSetups,
} from '@saucebase/vue/lib/moduleSetup';

/**
 * Used as a wrapper to global components
 */
import AppWrapper from '@/components/App.vue';

registerNavigationDefaults();

initializeModals(putConfig);

registerAppPages(import.meta.glob<DefineComponent>('./pages/**/*.vue'));

// Module setups only register components, icons and actions, so they run on the
// server too: both sides must render the same tree for the browser to hydrate it.
const moduleSetups = executeModuleSetups(discoverModuleSetups());

let vueApp: App | undefined;

// Before the app mounts, not after as Inertia's own `progress` option does: a
// component that visits on mount (a `#settings/...` deep link) would otherwise
// start a bar that isn't set up yet.
if (typeof document !== 'undefined') {
    setupProgress({
        color:
            getComputedStyle(document.documentElement)
                .getPropertyValue('--primary')
                .trim() || '#4B5563',
    });
}

// No `setup` callback: Inertia then hydrates server-rendered HTML itself
// (`createSSRApp`) and the Vite plugin builds the server entry from this file.
createInertiaApp({
    title: siteTitle,
    // The global components, as every page's layout: inside Inertia's app, so they
    // read this request's page. Wrapped around it they would render before the page
    // is set, hidden on hydration and, on the server, showing the previous request's.
    layout: () => AppWrapper,
    resolve: async (name) => {
        await moduleSetups;

        return resolveModularPageComponent(name);
    },
    withApp(app, { ssr, page }) {
        if (ssr) {
            const ziggy = ziggyForServer(page.props.ziggy as any);

            // The browser gets `route()` from the page's own script; the server has
            // to be handed this request's routes. Set here, straight before the
            // synchronous render, so one request never reads another's.
            (globalThis as any).route = (
                name?: string,
                params?: any,
                absolute?: boolean,
            ) => ziggyRoute(name as any, params, absolute, ziggy as any);

            app.use(ZiggyVue, ziggy as any);
        } else {
            app.use(ZiggyVue);

            // Initialize global theme persistence for proper Vue reactivity
            useColorMode({ storageKey: 'appearance' });
        }

        app.use(i18nVue, {
            lang: (page.props.locale as string) || 'en',
            resolve: resolveLanguage,
        });

        // Wrap the root render in ModalRoot so routes can be opened in a modal.
        // Modal pages resolve through the app's own component resolver, so
        // module pages work without extra registration.
        // Cast: the package types the root render more narrowly than Vue's
        // `App`, which declares it as a plain `Function`.
        withInertiaModal(
            app as unknown as Parameters<typeof withInertiaModal>[0],
        );

        vueApp = app;
    },
    // Set up below, before the app mounts.
    progress: false,
})
    .then(async () => {
        const afterMountCallbacks = await moduleSetups;

        return executeAfterMountCallbacks(afterMountCallbacks, vueApp);
    })
    .catch(console.error);
