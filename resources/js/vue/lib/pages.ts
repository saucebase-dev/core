import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';

declare const __SAUCEBASE_APP_JS__: string;
declare const __SAUCEBASE_CONTRIBUTOR__: boolean;
declare const __SAUCEBASE_FRAMEWORK__: string;

/** A contributor checkout keeps each framework's module files in their own folder; an installed app is flat. */
const tree = __SAUCEBASE_CONTRIBUTOR__ ? `${__SAUCEBASE_FRAMEWORK__}/` : '';

/**
 * Finds the component for an Inertia page name: `Dashboard` in the app's pages,
 * `Billing::Checkout` in the Billing module's.
 */
export function resolveModularPageComponent(name: string) {
    if (name.includes('::')) {
        const [moduleName, componentPath] = name.split('::', 2);
        const moduleFolderName = moduleName
            .replace(/([a-z])([A-Z])/g, '$1-$2')
            .toLowerCase();

        return resolvePageComponent(
            `/modules/${moduleFolderName}/resources/js/${tree}pages/${componentPath}.vue`,
            import.meta.glob<DefineComponent>(
                '/modules/*/resources/js/**/pages/**/*.vue',
            ),
        );
    }

    return resolvePageComponent(
        `${__SAUCEBASE_APP_JS__}/pages/${name}.vue`,
        // One or the other, so an installed app does not also build core's copy of its pages.
        __SAUCEBASE_CONTRIBUTOR__
            ? import.meta.glob<DefineComponent>(
                  '/vendor/saucebase/core/stubs/stack/vue/js/pages/**/*.vue',
              )
            : import.meta.glob<DefineComponent>('/resources/js/pages/**/*.vue'),
    );
}
