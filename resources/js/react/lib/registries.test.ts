import { describe, expect, it, vi } from 'vitest';
import { getGlobalComponents, hasGlobalComponent, registerGlobalComponent } from './globalComponents';
import { handleAction, registerAction, registerIcon, resolveIcon } from './navigation';

const Component = () => null;

describe('global components', () => {
    it('keeps what each slot was given, in order', () => {
        expect(hasGlobalComponent('top')).toBe(false);

        registerGlobalComponent('top', Component);

        expect(hasGlobalComponent('top')).toBe(true);
        expect(getGlobalComponents('top')).toEqual([Component]);
        expect(getGlobalComponents('bottom')).toEqual([]);
    });
});

describe('navigation', () => {
    it('runs the handler registered for an action', () => {
        const handler = vi.fn();
        const event = {} as MouseEvent;

        registerAction('billing.portal', handler);
        handleAction('billing.portal', event);

        expect(handler).toHaveBeenCalledWith(event);
    });

    it('starts with no actions or icons of its own', () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

        handleAction('ui.theme.toggle', {} as MouseEvent);

        expect(warn).toHaveBeenCalledWith('No handler registered for action: ui.theme.toggle');
        expect(resolveIcon('dashboard')).toBeUndefined();
        warn.mockRestore();
    });

    it('resolves a registered icon', () => {
        registerIcon('billing', Component);

        expect(resolveIcon('billing')).toBe(Component);
    });
});
