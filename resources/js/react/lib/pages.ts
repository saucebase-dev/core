import type { ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

declare const __SAUCEBASE_CONTRIBUTOR__: boolean;

/**
 * A contributor checkout keeps each framework's module files in their own folder; an
 * installed app's modules are flat, except the template the app builds, which keeps its
 * folder because it is published to other apps.
 */
const trees = __SAUCEBASE_CONTRIBUTOR__ ? ['react/'] : ['', 'react/'];

let appPages: Record<string, () => Promise<ResolvedComponent>> = {};

/**
 * Tells the resolver which pages are the app's. The app globs its own `pages` folder
 * from its entry and registers the result once, so where they live is the app's
 * business and not this file's.
 */
export function registerAppPages(
    pages: Record<string, () => Promise<ResolvedComponent>>,
): void {
    appPages = pages;
}

/**
 * Finds the component for an Inertia page name: `Dashboard` in the app's pages,
 * `Billing::Checkout` in the Billing module's.
 */
export function resolveModularPageComponent(
    name: string,
): Promise<ResolvedComponent> {
    if (name.includes('::')) {
        const [moduleName, componentPath] = name.split('::', 2);
        const moduleFolderName = moduleName
            .replace(/([a-z])([A-Z])/g, '$1-$2')
            .toLowerCase();

        const paths = trees.map(
            (tree) =>
                `/modules/${moduleFolderName}/resources/js/${tree}pages/${componentPath}.tsx`,
        );

        return resolvePageComponent(
            paths.length === 1 ? paths[0] : paths,
            import.meta.glob<ResolvedComponent>(
                '/modules/*/resources/js/**/pages/**/*.tsx',
            ),
        );
    }

    return resolvePageComponent(`./pages/${name}.tsx`, appPages);
}
