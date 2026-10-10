import { beforeEach, describe, expect, it, vi } from 'vitest';

// The hooks run once per call here: enough to read and write the fragment.
vi.mock('react', () => ({
    useCallback: (fn: unknown) => fn,
    useMemo: (fn: () => unknown) => fn(),
    useSyncExternalStore: (_subscribe: unknown, snapshot: () => unknown) =>
        snapshot(),
}));

const location = { hash: '', pathname: '/dashboard', search: '?tab=1' };
const replaceState = vi.fn((_state: unknown, _title: string, url: string) => {
    location.hash = url.includes('#') ? url.slice(url.indexOf('#')) : '';
});

const windowListeners: Record<string, () => void> = {};
const routerListeners: Record<string, () => void> = {};

vi.mock('@inertiajs/react', () => ({
    router: {
        on: (event: string, listener: () => void) => {
            routerListeners[event] = listener;
        },
    },
}));

vi.stubGlobal('window', {
    location,
    history: { state: { id: 1 }, replaceState },
    addEventListener: (event: string, listener: () => void) => {
        windowListeners[event] = listener;
    },
});

const { settingsHref, useSettingsModal } = await import('./useSettingsModal');

beforeEach(() => {
    location.hash = '';
    replaceState.mockClear();
});

describe('settingsHref', () => {
    it('addresses the modal and its sections by fragment', () => {
        expect(settingsHref()).toBe('#settings');
        expect(settingsHref('profile')).toBe('#settings/profile');
    });
});

describe('useSettingsModal', () => {
    it('is closed on any other fragment', () => {
        for (const hash of ['', '#pricing', '#settings-help']) {
            location.hash = hash;

            expect(useSettingsModal()).toMatchObject({
                isOpen: false,
                section: null,
            });
        }
    });

    it('reads the section from the fragment', () => {
        location.hash = '#settings';
        expect(useSettingsModal()).toMatchObject({
            isOpen: true,
            section: null,
        });

        location.hash = '#settings/billing';
        expect(useSettingsModal()).toMatchObject({
            isOpen: true,
            section: 'billing',
        });
    });

    it('opens by setting the fragment, so the browser fires hashchange', () => {
        useSettingsModal().open('security');

        expect(location.hash).toBe('#settings/security');
        expect(replaceState).not.toHaveBeenCalled();
    });

    it('closes by dropping the fragment and keeping the rest of the URL', () => {
        location.hash = '#settings/profile';

        useSettingsModal().close();

        expect(replaceState).toHaveBeenCalledWith(
            { id: 1 },
            '',
            '/dashboard?tab=1',
        );
    });

    it('does not touch history when closing something that is not open', () => {
        location.hash = '#pricing';

        useSettingsModal().close();

        expect(replaceState).not.toHaveBeenCalled();
    });

    it('restores a section without adding a history entry', () => {
        useSettingsModal().restore('profile');

        expect(replaceState).toHaveBeenCalledWith(
            { id: 1 },
            '',
            '/dashboard?tab=1#settings/profile',
        );
    });

    it('puts the fragment back when an Inertia navigation drops it', () => {
        location.hash = '#settings/profile';
        windowListeners.hashchange();

        // Inertia writes its own URL, which has no fragment, into the history entry.
        location.hash = '';
        routerListeners.navigate();

        expect(replaceState).toHaveBeenCalledWith(
            { id: 1 },
            '',
            '/dashboard?tab=1#settings/profile',
        );
    });

    it('does not undo a section change the browser has not announced yet', () => {
        location.hash = '#settings/profile';
        windowListeners.hashchange();

        // The fragment changes at once; `hashchange` only fires later.
        location.hash = '#settings/billing';
        routerListeners.navigate();

        expect(replaceState).not.toHaveBeenCalled();
    });

    it('leaves the URL alone after a navigation when settings are closed', () => {
        location.hash = '#pricing';
        windowListeners.hashchange();

        location.hash = '';
        routerListeners.navigate();

        expect(replaceState).not.toHaveBeenCalled();
    });
});
