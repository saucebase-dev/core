import { beforeEach, describe, expect, it, vi } from 'vitest';

const { visit, visitModal, props } = vi.hoisted(() => ({
    visit: vi.fn(),
    visitModal: vi.fn(),
    props: { auth: {} as { modal_enabled?: boolean } },
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { visit },
    usePage: () => ({ props }),
}));
vi.mock('@inertiaui/modal-vue', () => ({ visitModal }));
vi.mock('vue', () => ({
    computed: (get: () => unknown) => ({
        get value() {
            return get();
        },
    }),
}));

import { useAuth } from './useAuth';

const blur = vi.fn();

function click(extra: Partial<MouseEvent> = {}) {
    return {
        button: 0,
        preventDefault: vi.fn(),
        ...extra,
    } as unknown as MouseEvent;
}

beforeEach(() => {
    vi.clearAllMocks();
    props.auth = {};
    vi.stubGlobal('route', (name: string) => `/${name}`);
    vi.stubGlobal('document', { activeElement: { blur } });
});

describe('useAuth', () => {
    it('visits the page when modal sign-in is off', () => {
        const event = click();

        useAuth().login(event as never);

        expect(event.preventDefault).toHaveBeenCalled();
        expect(visit).toHaveBeenCalledWith('/login');
        expect(visitModal).not.toHaveBeenCalled();
    });

    it('opens the modal, with the route in the address bar, when modal sign-in is on', () => {
        props.auth = { modal_enabled: true };

        useAuth().signup(click() as never);

        expect(blur).toHaveBeenCalled();
        expect(visitModal).toHaveBeenCalledWith('/register', {
            navigate: true,
        });
        expect(visit).not.toHaveBeenCalled();
    });

    it('leaves a new-tab click to the browser', () => {
        props.auth = { modal_enabled: true };

        for (const extra of [
            { metaKey: true },
            { ctrlKey: true },
            { shiftKey: true },
            { button: 1 },
        ]) {
            const event = click(extra);

            useAuth().login(event as never);

            expect(event.preventDefault).not.toHaveBeenCalled();
        }

        expect(visitModal).not.toHaveBeenCalled();
        expect(visit).not.toHaveBeenCalled();
    });
});
