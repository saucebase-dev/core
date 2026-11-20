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
        documentElement: new EventTarget(),
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
    for (const key of ['document', 'window', 'KeyboardEvent', 'Element']) {
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

    const parts = [
        ...selector.matchAll(/(:not\()?\[([\w-]+)(?:(\$?=)"([^"]*)")?\]\)?/g),
    ];

    return (
        parts.length > 0 &&
        parts.every(
            ([, not, attribute, operator, value]) =>
                Boolean(not) !== test(attribute, operator, value),
        )
    );
}

/** A node in a tiny page: listeners by phase, and an event's way down and back up. */
class FakeNode {
    listeners: { listener: (event: object) => void; capture: boolean }[] = [];

    constructor(
        public parent: FakeNode | null = null,
        public attributes: Record<string, string> = {},
    ) {}

    addEventListener(
        _type: string,
        listener: (event: object) => void,
        capture = false,
    ): void {
        this.listeners.push({ listener, capture });
    }

    contains(other: FakeNode | null): boolean {
        for (let node = other; node; node = node.parent) {
            if (node === this) return true;
        }

        return false;
    }

    closest(selector: string): FakeNode | null {
        for (let node: FakeNode | null = this; node; node = node.parent) {
            const attributes = node.attributes;
            if (
                selector
                    .split(',')
                    .some((part) => matches(attributes, part.trim()))
            ) {
                return node;
            }
        }

        return null;
    }
}

/** document > html > body > the modal; React listens on body, the portals' container. */
function page(): { document: FakeNode; body: FakeNode } {
    const document = new FakeNode();
    const html = new FakeNode(document);
    const body = new FakeNode(html);
    const modal = new FakeNode(body, { class: 'im-modal-wrapper' });

    Object.assign(document, {
        documentElement: html,
        querySelector: (selector: string) =>
            selector === '.im-modal-wrapper' ? modal : null,
        querySelectorAll: () => [],
    });
    Object.assign(globalThis, { document, Element: FakeNode });
    initializeModals(() => {});

    return { document, body };
}

/** focusin down from the document (capture), then back up (bubble), until stopped. */
function focus(target: FakeNode): void {
    const path: FakeNode[] = [];
    for (let node: FakeNode | null = target; node; node = node.parent) {
        path.push(node);
    }

    let stopped = false;
    const event = {
        type: 'focusin',
        target,
        stopPropagation: () => (stopped = true),
    };

    for (const [nodes, capture] of [
        [[...path].reverse(), true],
        [path, false],
    ] as const) {
        for (const node of nodes) {
            if (stopped) return;
            node.listeners
                .filter((entry) => entry.capture === capture)
                .forEach((entry) => entry.listener(event));
        }
    }
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
        const heard = (slot: string, state = 'open'): boolean => {
            const element = { 'data-slot': slot, 'data-state': state };
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
        // Radix's tooltip is never "open", only delayed or instant.
        expect(heard('tooltip-content', 'delayed-open')).toBe(true);
        expect(heard('dialog-content', 'closed')).toBe(false);
    });

    /** A layer may refuse Escape (mid-submit, say); the refusal must hold. */
    it('keeps a refusal from the layer above', () => {
        window.addEventListener('keydown', (event) => event.preventDefault());

        expect(pressEscape().defaultPrevented).toBe(true);
    });
});

describe('Focus while a modal is open', () => {
    /** React reads onFocus from focusin on its containers; the traps listen on document. */
    it('reaches an overlay above, but not the modal trap', () => {
        const { document, body } = page();
        const input = new FakeNode(
            new FakeNode(body, { 'data-slot': 'popover-content' }),
        );
        const heard: string[] = [];
        body.addEventListener('focusin', () => heard.push('react'));
        document.addEventListener('focusin', () => heard.push('trap'));

        focus(input);

        expect(heard).toEqual(['react']);
    });

    /** Page content behind the modal is no overlay: the trap pulls focus back from it. */
    it('is left to the trap in page content behind the modal', () => {
        const { document, body } = page();
        const link = new FakeNode(
            new FakeNode(body, { 'data-slot': 'card-content' }),
        );
        const heard: string[] = [];
        body.addEventListener('focusin', () => heard.push('react'));
        document.addEventListener('focusin', () => heard.push('trap'));

        focus(link);

        expect(heard).toEqual(['react', 'trap']);
    });
});
