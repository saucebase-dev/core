import { beforeEach, describe, expect, it, vi } from 'vitest';

const { toast } = vi.hoisted(() => {
    const toast = Object.assign(vi.fn(), {
        success: vi.fn(),
        error: vi.fn(),
        info: vi.fn(),
        warning: vi.fn(),
        loading: vi.fn(),
    });

    return { toast };
});

vi.mock('sonner', () => ({ toast }));

import { showToast } from './toastActions';

beforeEach(() => vi.clearAllMocks());

describe('showToast', () => {
    it('shows the flashed toast with its type and options', () => {
        showToast({
            message: 'Saved',
            type: 'success',
            description: 'All good',
            duration: 3000,
        });

        expect(toast.success).toHaveBeenCalledWith('Saved', {
            description: 'All good',
            duration: 3000,
            position: undefined,
        });
    });

    it('uses the matching toast for every type', () => {
        for (const type of ['error', 'info', 'warning', 'loading'] as const) {
            showToast({ message: 'Hi', type });

            expect(toast[type]).toHaveBeenCalledTimes(1);
        }

        showToast({ message: 'Hi', type: 'default' });
        expect(toast).toHaveBeenCalledTimes(1);
    });

    it('shows nothing for a toast without a message', () => {
        showToast({ message: '', type: 'success' });

        expect(toast).not.toHaveBeenCalled();
        expect(toast.success).not.toHaveBeenCalled();
    });
});
