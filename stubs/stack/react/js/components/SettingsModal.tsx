import { useModalStack, type ModalInstance } from '@inertiaui/modal-react';
import { useSettingsModal } from '@saucebase/react/hooks/useSettingsModal';
import { useEffect, useRef } from 'react';

/**
 * Opens and closes the settings modal in step with the `#settings` fragment.
 *
 * Rendered once at the app root so settings can be opened from anywhere without
 * the surrounding page having to know about it.
 */
export default function SettingsModal() {
    const { isOpen, section, close } = useSettingsModal();
    const { visitModal } = useModalStack();

    const modal = useRef<ModalInstance | null>(null);
    const opening = useRef(false);

    /**
     * Held in a ref rather than read as a dependency: the open modal swaps
     * panels in place from the fragment, so a section change must not tear it
     * down and open a second one.
     */
    const requested = useRef(section);
    requested.current = section;

    /** Read when the request lands, which may be after the user closed settings. */
    const wanted = useRef(isOpen);
    wanted.current = isOpen;

    useEffect(() => {
        if (!isOpen) {
            modal.current?.close();
            modal.current = null;

            return;
        }

        if (modal.current || opening.current) {
            return;
        }

        opening.current = true;

        visitModal(
            route(
                'settings',
                requested.current ? { section: requested.current } : {},
            ),
            {
                // Closing from inside the modal (escape, backdrop, close
                // button) has to clear the fragment too, or it could not be
                // reopened.
                onClose: () => {
                    modal.current = null;
                    close();
                },
            },
        )
            .then((instance) => {
                // Closed while the request was in flight (Back, for one): there was
                // no modal to close then, so the late response must not open one now.
                if (wanted.current) {
                    modal.current = instance;
                } else {
                    instance.close();
                }
            })
            .finally(() => {
                opening.current = false;
            });
    }, [isOpen, close, visitModal]);

    return null;
}
