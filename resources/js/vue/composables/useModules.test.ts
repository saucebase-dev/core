import { beforeEach, describe, expect, it, vi } from 'vitest';

const { props } = vi.hoisted(() => ({
    props: {} as { modules?: Record<string, string> },
}));

vi.mock('@inertiajs/vue3', () => ({ usePage: () => ({ props }) }));

import { modules, useModules } from './useModules';

beforeEach(() => {
    props.modules = { Auth: 'auth', BillingPortal: 'billing-portal' };
});

describe('useModules', () => {
    it('finds a module by its name or its identifier, in any case', () => {
        expect(useModules().has('auth')).toBe(true);
        expect(useModules().has('Auth')).toBe(true);
        expect(useModules().has('billing-portal')).toBe(true);
        expect(useModules().has('BILLINGPORTAL')).toBe(true);
    });

    it('says no for a module that is not installed', () => {
        expect(modules().has('roadmap')).toBe(false);
    });

    it('lists the installed identifiers', () => {
        expect(useModules().all()).toEqual(['auth', 'billing-portal']);
    });

    it('copes with a page that shares no modules', () => {
        delete props.modules;

        expect(useModules().has('auth')).toBe(false);
        expect(useModules().all()).toEqual([]);
    });
});
