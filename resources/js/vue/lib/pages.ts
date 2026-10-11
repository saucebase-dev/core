import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';

declare const __SAUCEBASE_CONTRIBUTOR__: boolean;

/** A contributor checkout keeps each framework's module files in their own folder; an installed app is flat. */
const tree = __SAUCEBASE_CONTRIBUTOR__ ? 'vue/' : '';

let appPages: Record<string, () => Promise<DefineComponent>> = {};

/**
 * Tells the resolver which pages are the app's. The app globs its own `pages` folder
 * from its entry and registers the result once, so where they live is the app's
 * business and not this file's.
 */
export function registerAppPages(
    pages: Record<string, () => Promise<DefineComponent>>,
): void {
    appPages = pages;
}

/**
 * Finds the component for an Inertia page name: `Dashboard` in the app's pages,
 * `Billing::Checkout` in the Billing module's.
 */
export function resolveModularPageComponent(
    name: string,
): Promise<DefineComponent> {
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

    return resolvePageComponent(`./pages/${name}.vue`, appPages);
}
