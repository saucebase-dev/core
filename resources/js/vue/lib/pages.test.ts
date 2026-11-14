import { afterEach, describe, expect, it, vi } from 'vitest';

// Returns the path it was asked for and the pages it was given, so the tests read which
// file a page name maps to and where it is looked up.
vi.mock('laravel-vite-plugin/inertia-helpers', () => ({
    resolvePageComponent: (path: string | string[], pages: object) => ({
        path,
        pages,
    }),
}));

const appPages = {};

async function resolve(name: string, contributor: boolean) {
    vi.resetModules();
    vi.stubGlobal('__SAUCEBASE_CONTRIBUTOR__', contributor);

    const { registerAppPages, resolveModularPageComponent } =
        await import('./pages');
    registerAppPages(appPages);

    return resolveModularPageComponent(name) as unknown as {
        path: string | string[];
        pages: object;
    };
}

afterEach(() => vi.unstubAllGlobals());

describe('resolveModularPageComponent', () => {
    it('finds app pages among the ones the app registered', async () => {
        const page = await resolve('Dashboard', false);

        expect(page.path).toBe('./pages/Dashboard.vue');
        expect(page.pages).toBe(appPages);
        expect((await resolve('Errors/NotFound', true)).path).toBe(
            './pages/Errors/NotFound.vue',
        );
    });

    it('finds module pages, turning the module name into its folder', async () => {
        const page = await resolve('Billing::Checkout', false);

        expect(page.path).toContain(
            '/modules/billing/resources/js/pages/Checkout.vue',
        );
        expect(page.pages).not.toBe(appPages);
        expect(
            (await resolve('BillingPortal::Plans/Index', false)).path,
        ).toContain(
            '/modules/billing-portal/resources/js/pages/Plans/Index.vue',
        );
    });

    /** The app's own template keeps its stack folder: it is published to apps of that stack. */
    it('finds the pages of a module that keeps its stack folder in an installed app', async () => {
        expect((await resolve('Notes::Index', false)).path).toEqual([
            '/modules/notes/resources/js/pages/Index.vue',
            '/modules/notes/resources/js/vue/pages/Index.vue',
        ]);
    });

    it('finds module pages in a contributor checkout', async () => {
        expect((await resolve('Billing::Checkout', true)).path).toBe(
            '/modules/billing/resources/js/vue/pages/Checkout.vue',
        );
    });
});
