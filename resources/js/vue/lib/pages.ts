import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';

declare const __SAUCEBASE_DEV__: boolean;
declare const __SAUCEBASE_FRAMEWORK__: string;

/** A contributor checkout keeps each framework's tree in its own folder; an installed app is flat. */
const tree = __SAUCEBASE_DEV__ ? `${__SAUCEBASE_FRAMEWORK__}/` : '';

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
        `/resources/js/${tree}pages/${name}.vue`,
        import.meta.glob<DefineComponent>([
            '/resources/js/pages/**/*.vue',
            '/resources/js/vue/pages/**/*.vue',
        ]),
    );
}
