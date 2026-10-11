import { afterEach, describe, expect, it, vi } from 'vitest';

// Returns the path it was asked for, so the tests read which file a page name maps to.
vi.mock('laravel-vite-plugin/inertia-helpers', () => ({
    resolvePageComponent: (path: string) => path,
}));

async function resolve(name: string, dev: boolean) {
    vi.resetModules();
    vi.stubGlobal('__SAUCEBASE_CONTRIBUTOR__', dev);
    vi.stubGlobal('__SAUCEBASE_FRAMEWORK__', 'vue');
    vi.stubGlobal(
        '__SAUCEBASE_APP_JS__',
        dev ? '/vendor/saucebase/core/stubs/stack/vue/js' : '/resources/js',
    );

    const { resolveModularPageComponent } = await import('./pages');

    return resolveModularPageComponent(name);
}

afterEach(() => vi.unstubAllGlobals());

describe('resolveModularPageComponent', () => {
    it('finds app pages in an installed app', async () => {
        expect(await resolve('Dashboard', false)).toBe(
            '/resources/js/pages/Dashboard.vue',
        );
        expect(await resolve('Errors/NotFound', false)).toBe(
            '/resources/js/pages/Errors/NotFound.vue',
        );
    });

    it('finds app pages in a contributor checkout', async () => {
        expect(await resolve('Dashboard', true)).toBe(
            '/vendor/saucebase/core/stubs/stack/vue/js/pages/Dashboard.vue',
        );
    });

    it('finds module pages, turning the module name into its folder', async () => {
        expect(await resolve('Billing::Checkout', false)).toBe(
            '/modules/billing/resources/js/pages/Checkout.vue',
        );
        expect(await resolve('BillingPortal::Plans/Index', false)).toBe(
            '/modules/billing-portal/resources/js/pages/Plans/Index.vue',
        );
    });

    it('finds module pages in a contributor checkout', async () => {
        expect(await resolve('Billing::Checkout', true)).toBe(
            '/modules/billing/resources/js/vue/pages/Checkout.vue',
        );
    });
});
