type PutConfig = (key: string, value: unknown) => void;

/**
 * The Inertia modal package's setup, shared by both stacks.
 *
 * `putConfig` is passed in because each stack imports it from its own build of
 * the package; everything it is given is the same either way.
 */
export function initializeModals(putConfig: PutConfig): void {
    // A native `<dialog>` sits in the top layer, where it would cover the
    // app-level confirm dialog and make it unclickable from inside a modal.
    putConfig('useNativeDialog', false);

    // The package defaults the panel to a hardcoded `bg-white`, which breaks
    // dark mode. Set key by key so its other defaults stay in place.
    putConfig(
        'modal.panelClasses',
        'bg-background overflow-hidden rounded-lg border shadow-lg',
    );
    putConfig(
        'slideover.panelClasses',
        'bg-background min-h-screen overflow-hidden border-l shadow-lg',
    );

    releaseCoveredModalFocus();
    escapeClosesTheTopLayerFirst();
}

/**
 * Overlays in core's shadcn UI, by content slot. Not every `-content` slot: a card, a
 * field or an open collapsible (the sidebar's nav groups) has one too, and is no layer.
 */
const OVERLAY_CONTENT = [
    'dialog',
    'alert-dialog',
    'sheet',
    'popover',
    'dropdown-menu',
    'dropdown-menu-sub',
    'select',
    'tooltip',
].map((slot) => `[data-slot="${slot}-content"]`);

/**
 * Open layers (menus, selects, popovers, dialogs) in either stack: reka-ui (Vue) marks
 * its own; Radix (React) marks none, so its are found by shadcn's content slot. Not
 * closed, rather than open: a Radix tooltip is `delayed-open` or `instant-open`.
 */
const OPEN_LAYERS = ['[data-dismissable-layer]', ...OVERLAY_CONTENT]
    .map((layer) => `${layer}:not([data-state="closed"])`)
    .join(', ');

/**
 * Escape closes what is on top: a menu, select or dialog opened over a modal, not the
 * modal under it.
 *
 * The modal listens for Escape on `document` and reka-ui's layers on `window`, so the
 * modal hears it first and closes, taking the open menu and whatever was typed with
 * it. Caught here on the way down instead: while a reka layer outside the modal is
 * open, the key goes to `window` alone, where only reka is listening.
 */
function escapeClosesTheTopLayerFirst(): void {
    if (typeof document === 'undefined') return;

    document.addEventListener(
        'keydown',
        (event) => {
            if (
                event.key !== 'Escape' ||
                !document.querySelector('.im-modal-wrapper')
            ) {
                return;
            }

            // A closing layer stays in the page until its exit animation ends.
            const layerAbove = [...document.querySelectorAll(OPEN_LAYERS)].some(
                (layer) => !layer.closest('.im-modal-wrapper'),
            );

            if (layerAbove) {
                event.stopPropagation();
                // Cancelable, and its answer carried back: a layer that refuses Escape
                // (mid-submit, say) stays open, and the browser's default stays off too.
                const forwarded = new KeyboardEvent('keydown', {
                    key: 'Escape',
                    bubbles: true,
                    cancelable: true,
                });

                if (!window.dispatchEvent(forwarded)) {
                    event.preventDefault();
                }
            }
        },
        true,
    );
}

/**
 * Stop the modal's focus trap from fighting overlays stacked above it.
 *
 * With `useNativeDialog` off the modal traps focus with a document-level
 * `focusin` listener that pulls focus back into its wrapper. A dialog, menu or
 * popover portalled to the body sits outside that wrapper and traps focus the
 * same way, so the two pull against each other on one event until the stack
 * overflows.
 *
 * An overlay marks everything it covered with `data-aria-hidden` when it opens,
 * so a covered modal has an overlay above it and no business holding focus. A
 * non-modal layer (popover, combobox) marks nothing, so focus landing inside one
 * counts too. Every trap (the modal's, reka-ui's, Radix's) listens on `document`, so
 * stopping the event on `<html>` on its way up keeps it from all of them, after
 * React has read its `onFocus` from it on its own containers below.
 */
function releaseCoveredModalFocus(): void {
    // The server renders the same app but has no document to listen on.
    if (typeof document === 'undefined') return;

    document.documentElement.addEventListener('focusin', (event) => {
        const modal = document.querySelector('.im-modal-wrapper');
        const target = event.target;

        if (!modal || !(target instanceof Element) || modal.contains(target)) {
            return;
        }

        // Covered by a modal layer, or focus moving into a non-modal one (a popover
        // or combobox opened from the modal): either way the trap must let go, or
        // it pulls focus back and the layer closes as soon as it opens.
        const covered = modal.closest('[data-aria-hidden]') !== null;
        const intoLayer =
            target.closest(
                [
                    '[data-reka-popper-content-wrapper]',
                    '[data-dismissable-layer]',
                    '[data-radix-popper-content-wrapper]',
                    ...OVERLAY_CONTENT,
                ].join(', '),
            ) !== null;

        if (covered || intoLayer) {
            event.stopPropagation();
        }
    });
}
