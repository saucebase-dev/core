import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { siteTitle } from '@saucebase/core/lib/settings';
import { ziggyForServer } from '@saucebase/core/lib/ziggy';
import { resolveLanguageForSsr } from '@saucebase/vue/lib/language';
import {
    registerAppPages,
    resolveModularPageComponent,
} from '@saucebase/vue/lib/pages';
import { i18nVue } from 'laravel-vue-i18n';
import { createSSRApp, h, type DefineComponent } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { route as ziggyRoute, ZiggyVue } from 'ziggy-js';
import { registerNavigationDefaults } from './lib/navigation';

/**
 * Used as a wrapper to global components
 */
import App from '@/components/App.vue';

registerNavigationDefaults();

registerAppPages(import.meta.glob<DefineComponent>('./pages/**/*.vue'));

createServer(
    (page) => {
        return createInertiaApp({
            page,
            render: renderToString,
            title: siteTitle,
            resolve: resolveModularPageComponent,
            setup({ App: InertiaApp, props, plugin }) {
                const ziggy = ziggyForServer(page.props.ziggy as any);

                // The browser gets `route()` from the page's own script; the server has
                // to be handed this request's routes. Set here, straight before the
                // synchronous render, so one request never reads another's.
                (globalThis as any).route = (
                    name?: string,
                    params?: any,
                    absolute?: boolean,
                ) => ziggyRoute(name as any, params, absolute, ziggy as any);

                const app = createSSRApp({
                    render: () => h(App, {}, () => h(InertiaApp, props)),
                })
                    .use(plugin)
                    .use(ZiggyVue, ziggy as any)
                    .use(i18nVue, {
                        lang: (page.props.locale as string) || 'en',
                        resolve: resolveLanguageForSsr,
                    });

                // Note: Module setups are not executed in SSR context
                // They contain browser-specific code (event handlers, DOM interactions)
                // and are only needed in the client-side app.ts

                return app;
            },
        });
    },
    {
        cluster: true,
    },
);
