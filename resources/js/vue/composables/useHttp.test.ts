import { beforeEach, describe, expect, it, vi } from 'vitest';

const { toastError, post } = vi.hoisted(() => ({
    toastError: vi.fn(),
    post: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useHttp: () => ({
        get: vi.fn(),
        post,
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
    }),
}));
vi.mock('laravel-vue-i18n', () => ({ trans: (message: string) => message }));
vi.mock('vue-sonner', () => ({ toast: { error: toastError } }));

import { useHttp } from './useHttp';

/** Makes the next request fail the way Inertia reports it, then reject. */
function failWith(status?: number) {
    post.mockImplementation(
        (
            _url: string,
            options: Record<string, (response?: unknown) => void>,
        ) => {
            if (status === undefined) {
                options.onNetworkError();
            } else {
                options.onHttpException({ status });
            }

            return Promise.reject(new Error('failed'));
        },
    );
}

beforeEach(() => {
    vi.clearAllMocks();
    vi.stubGlobal('navigator', { onLine: true });
});

describe('useHttp', () => {
    it('shows a toast and resolves undefined when the session expired', async () => {
        failWith(419);

        await expect(useHttp().post('/vote')).resolves.toBeUndefined();
        expect(toastError).toHaveBeenCalledWith(
            'Your session expired. Refresh the page and try again.',
        );
    });

    it('says the user is offline only when the browser says so', async () => {
        failWith();
        await useHttp().post('/vote');
        expect(toastError).toHaveBeenLastCalledWith(
            'Something went wrong. Try again.',
        );

        vi.stubGlobal('navigator', { onLine: false });
        await useHttp().post('/vote');
        expect(toastError).toHaveBeenLastCalledWith(
            "You're offline. Check your connection and try again.",
        );
    });

    it('hands the message to notify instead of a toast', async () => {
        failWith(500);
        const notify = vi.fn();

        await useHttp({}, notify).post('/vote');

        expect(notify).toHaveBeenCalledWith(
            'Something went wrong. Try again.',
            expect.anything(),
        );
        expect(toastError).not.toHaveBeenCalled();
    });

    it("lets a call's own handler replace the toast", async () => {
        failWith(500);
        const onHttpException = vi.fn();

        await useHttp().post('/vote', { onHttpException } as never);

        expect(onHttpException).toHaveBeenCalled();
        expect(toastError).not.toHaveBeenCalled();
    });
});
