import type { ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

declare const __SAUCEBASE_DEV__: boolean;
declare const __SAUCEBASE_FRAMEWORK__: string;

/** A contributor checkout keeps each framework's tree in its own folder; an installed app is flat. */
const tree = __SAUCEBASE_DEV__ ? `${__SAUCEBASE_FRAMEWORK__}/` : '';

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

        return resolvePageComponent(
            `/modules/${moduleFolderName}/resources/js/${tree}pages/${componentPath}.tsx`,
            import.meta.glob<ResolvedComponent>(
                '/modules/*/resources/js/**/pages/**/*.tsx',
            ),
        );
    }

    return resolvePageComponent(
        `/resources/js/${tree}pages/${name}.tsx`,
        import.meta.glob<ResolvedComponent>([
            '/resources/js/pages/**/*.tsx',
            '/resources/js/react/pages/**/*.tsx',
        ]),
    );
}
