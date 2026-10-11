import {
    revealTransition,
    type RevealOrigin,
} from '@saucebase/core/lib/themeReveal';
import { useCallback, useSyncExternalStore } from 'react';

export type Theme = 'light' | 'dark' | 'auto';

const STORAGE_KEY = 'appearance';

function setCookie(value: Theme): void {
    document.cookie = `appearance=${value};path=/;max-age=${365 * 24 * 60 * 60};SameSite=Lax`;
}

function applyTheme(theme: Theme): void {
    const prefersDark = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    const isDark = theme === 'dark' || (theme === 'auto' && prefersDark);
    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
}

function storedTheme(): Theme {
    return (localStorage.getItem(STORAGE_KEY) as Theme) || 'auto';
}

const listeners = new Set<() => void>();

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

export function initializeTheme(): void {
    applyTheme(storedTheme());

    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', () => {
            if (storedTheme() === 'auto') applyTheme('auto');
        });
}

export function useTheme() {
    // The preference lives in the browser, so the server renders `auto` and the
    // stored one takes over once the page hydrates.
    const theme = useSyncExternalStore<Theme>(
        subscribe,
        storedTheme,
        () => 'auto',
    );

    const setTheme = useCallback(
        (next: Theme, origin: RevealOrigin, animate = true) => {
            localStorage.setItem(STORAGE_KEY, next);
            setCookie(next);

            const apply = () => {
                applyTheme(next);
                listeners.forEach((listener) => listener());
            };

            if (!animate) {
                apply();
                return;
            }

            void revealTransition(origin, apply);
        },
        [],
    );

    return { theme, setTheme };
}
