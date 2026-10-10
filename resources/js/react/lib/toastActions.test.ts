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
vi.mock('react', () => ({ useEffect: (effect: () => void) => effect() }));

import { useToastListener } from './toastActions';

beforeEach(() => vi.clearAllMocks());

describe('useToastListener', () => {
    it('shows the flashed toast with its type and options', () => {
        useToastListener({
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
            useToastListener({ message: 'Hi', type });

            expect(toast[type]).toHaveBeenCalledTimes(1);
        }

        useToastListener({ message: 'Hi', type: 'default' });
        expect(toast).toHaveBeenCalledTimes(1);
    });

    it('shows nothing when the page flashed no toast', () => {
        useToastListener(undefined);

        expect(toast).not.toHaveBeenCalled();
        expect(toast.success).not.toHaveBeenCalled();
    });
});
