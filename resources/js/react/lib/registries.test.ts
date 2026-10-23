import { describe, expect, it, vi } from 'vitest';
import {
    getGlobalComponents,
    hasGlobalComponent,
    registerGlobalComponent,
    type GlobalComponentSlot,
} from './globalComponents';
import {
    handleAction,
    registerAction,
    registerIcon,
    resolveIcon,
} from './navigation';

const Component = () => null;

describe('global components', () => {
    it('keeps what each slot was given', () => {
        expect(hasGlobalComponent('app::page.before')).toBe(false);

        registerGlobalComponent('app::page.before', Component);

        expect(hasGlobalComponent('app::page.before')).toBe(true);
        expect(getGlobalComponents('app::page.before')).toEqual([Component]);
        expect(getGlobalComponents('app::page.after')).toEqual([]);
    });

    it('renders lower order first, ties in registration order, default 100', () => {
        const First = () => null;
        const Default = () => null;
        const SameAsDefault = () => null;
        const Last = () => null;

        registerGlobalComponent('home::content.after', Last, { order: 200 });
        registerGlobalComponent('home::content.after', Default);
        registerGlobalComponent('home::content.after', SameAsDefault, {
            order: 100,
        });
        registerGlobalComponent('home::content.after', First, { order: 10 });

        expect(getGlobalComponents('home::content.after')).toEqual([
            First,
            Default,
            SameAsDefault,
            Last,
        ]);
    });

    it('keeps one entry when a component registers again', () => {
        // The dev server re-runs module setups on the server after an edit.
        const Banner = () => null;
        const Other = () => null;

        registerGlobalComponent('site::header.before', Banner, { order: 10 });
        registerGlobalComponent('site::header.before', Other, { order: 20 });
        registerGlobalComponent('site::header.before', Banner, { order: 30 });

        expect(getGlobalComponents('site::header.before')).toEqual([
            Other,
            Banner,
        ]);
    });

    it('accepts a slot a module declares itself', () => {
        const slot = 'blog::post.after' as GlobalComponentSlot;

        expect(getGlobalComponents(slot)).toEqual([]);

        registerGlobalComponent(slot, Component);

        expect(getGlobalComponents(slot)).toEqual([Component]);
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

        expect(warn).toHaveBeenCalledWith(
            'No handler registered for action: ui.theme.toggle',
        );
        expect(resolveIcon('dashboard')).toBeUndefined();
        warn.mockRestore();
    });

    it('resolves a registered icon', () => {
        registerIcon('billing', Component);

        expect(resolveIcon('billing')).toBe(Component);
    });
});
