import { afterEach, beforeEach, describe, expect, it } from 'vitest';

import { initializeModals } from './modal';

/** Just enough of a page: a modal open, and a layer (a menu, a dialog) above it. */
class FakeKeyboardEvent extends Event {
    key: string;

    constructor(type: string, init: KeyboardEventInit = {}) {
        super(type, init);
        this.key = init.key ?? '';
    }
}

const layerAbove = { closest: () => null };

/** reka marks its layers; Radix (React) doesn't, so its open layers are found by shadcn's slot. */
let layerSelector = '[data-dismissable-layer]';

beforeEach(() => {
    const document = Object.assign(new EventTarget(), {
        querySelector: (selector: string) =>
            selector === '.im-modal-wrapper' ? {} : null,
        querySelectorAll: (selector: string) =>
            selector.includes(layerSelector) ? [layerAbove] : [],
    });

    Object.assign(globalThis, {
        document,
        window: new EventTarget(),
        KeyboardEvent: FakeKeyboardEvent,
    });
    initializeModals(() => {});
});

afterEach(() => {
    for (const key of ['document', 'window', 'KeyboardEvent']) {
        Reflect.deleteProperty(globalThis, key);
    }
});

/** Just enough of CSS attribute selectors: `[a]`, `[a="v"]`, `[a$="v"]` and `:not(...)` of those. */
function matches(element: Record<string, string>, selector: string): boolean {
    const test = (
        attribute: string,
        operator?: string,
        value?: string,
    ): boolean => {
        const actual = element[attribute];
        if (operator === undefined) return actual !== undefined;
        if (actual === undefined) return false;

        return operator === '$=' ? actual.endsWith(value!) : actual === value;
    };

    return [
        ...selector.matchAll(/(:not\()?\[([\w-]+)(?:(\$?=)"([^"]*)")?\]\)?/g),
    ].every(
        ([, not, attribute, operator, value]) =>
            Boolean(not) !== test(attribute, operator, value),
    );
}

function pressEscape(): Event {
    const event = new FakeKeyboardEvent('keydown', {
        key: 'Escape',
        bubbles: true,
        cancelable: true,
    });
    document.dispatchEvent(event);

    return event;
}

describe('Escape over a modal', () => {
    it('goes to the layer above, not the modal', () => {
        let layerHeardIt = false;
        window.addEventListener('keydown', () => (layerHeardIt = true));

        pressEscape();

        expect(layerHeardIt).toBe(true);
    });

    /**
     * React's menus and selects (Radix) carry no layer mark, only shadcn's open content slot.
     * An open collapsible (the sidebar's nav groups) carries one too, but is no layer: Escape
     * must still reach the modal.
     */
    it('goes to a Radix overlay above, not to any open content slot', () => {
        const heard = (slot: string): boolean => {
            const element = { 'data-slot': slot, 'data-state': 'open' };
            Object.assign(document, {
                querySelectorAll: (selector: string) =>
                    selector
                        .split(',')
                        .some((part) => matches(element, part.trim()))
                        ? [layerAbove]
                        : [],
            });
            let layerHeardIt = false;
            const listener = () => (layerHeardIt = true);
            window.addEventListener('keydown', listener);
            pressEscape();
            window.removeEventListener('keydown', listener);

            return layerHeardIt;
        };

        expect(heard('collapsible-content')).toBe(false);
        expect(heard('sidebar-group-content')).toBe(false);
        expect(heard('dropdown-menu-content')).toBe(true);
        expect(heard('select-content')).toBe(true);
        expect(heard('dialog-content')).toBe(true);
    });

    /** A layer may refuse Escape (mid-submit, say); the refusal must hold. */
    it('keeps a refusal from the layer above', () => {
        window.addEventListener('keydown', (event) => event.preventDefault());

        expect(pressEscape().defaultPrevented).toBe(true);
    });
});
