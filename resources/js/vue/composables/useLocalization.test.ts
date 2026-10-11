import { afterEach, describe, expect, it, vi } from 'vitest';

const page = { props: { locale: 'en' } };

// Just enough of Vue to hold and read a value.
vi.mock('vue', () => ({
    ref: (value: unknown) => ({ value }),
    readonly: (value: unknown) => value,
    computed: (get: () => unknown) => ({
        get value() {
            return get();
        },
    }),
    watch: vi.fn(),
}));
vi.mock('@inertiajs/vue3', () => ({ usePage: () => page }));
vi.mock('laravel-vue-i18n', () => ({ loadLanguageAsync: vi.fn() }));
vi.mock('./useHttp', () => ({ useHttp: () => ({ post: vi.fn() }) }));

async function languageFor(locale: string) {
    page.props.locale = locale;

    const { useLocalization } = await import('./useLocalization');

    return useLocalization().language.value;
}

afterEach(() => {
    vi.unstubAllGlobals();
    vi.resetModules();
});

describe('useLocalization', () => {
    // The server process outlives a request, so nothing may carry over to the next one.
    it('gives every server-rendered request its own language', async () => {
        expect(await languageFor('fr')).toBe('fr');
        expect(await languageFor('en')).toBe('en');
    });

    it('shares one language across the components of a browser tab', async () => {
        vi.stubGlobal('window', {});

        expect(await languageFor('fr')).toBe('fr');
        // A second component mounting later reads the tab's language, not its own copy.
        expect(await languageFor('en')).toBe('fr');
    });
});
