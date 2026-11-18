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

    /** React's menus and selects (Radix) carry no layer mark, only shadcn's open content slot. */
    it('goes to a Radix layer above too', () => {
        layerSelector = '[data-slot$="-content"][data-state="open"]';
        let layerHeardIt = false;
        window.addEventListener('keydown', () => (layerHeardIt = true));

        pressEscape();
        layerSelector = '[data-dismissable-layer]';

        expect(layerHeardIt).toBe(true);
    });

    /** A layer may refuse Escape (mid-submit, say); the refusal must hold. */
    it('keeps a refusal from the layer above', () => {
        window.addEventListener('keydown', (event) => event.preventDefault());

        expect(pressEscape().defaultPrevented).toBe(true);
    });
});
