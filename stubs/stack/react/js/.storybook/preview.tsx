import '@css/app.css';
import { App, router } from '@inertiajs/react';
import addonA11y from '@storybook/addon-a11y';
import addonDocs from '@storybook/addon-docs';
import addonThemes, { withThemeByClassName } from '@storybook/addon-themes';
import addonVitest from '@storybook/addon-vitest';
import { action } from 'storybook/actions';
import { definePreview } from '@storybook/react-vite';
import { registerNavigationDefaults } from '@/lib/navigation';
import { I18nProvider } from '@saucebase/react/i18n';
import { page } from './page';

// Ziggy's route(): Storybook has no routes, so nothing is current and a name is its own URL.
globalThis.route = ((name?: string) =>
    name === undefined ? { current: () => false } : `/${name}`) as typeof route;

// A story has no server behind it: a visit would load Storybook itself into the frame.
// Cancel every Inertia visit and show it in the Actions panel instead.
router.on('before', (event) => {
    const { method, url } = event.detail.visit;
    action('inertia visit')(method.toUpperCase(), url.pathname + url.search);

    return false;
});

// What app.ts registers before the first render: the navigation's icons and actions.
registerNavigationDefaults();

export const preview = definePreview({
    addons: [addonDocs(), addonThemes(), addonA11y(), addonVitest()],
    decorators: [
        // Every story renders inside Inertia's App, so usePage() works, and
        // inside the translations, as in app.tsx. A story sets its
        // own page props with `parameters: { inertia: { ... } }`.
        (Story, { parameters }) => (
            <I18nProvider initialLocale="en">
                <App
                    initialPage={page(parameters.inertia)}
                    initialComponent={Story}
                    resolveComponent={() => Story}
                />
            </I18nProvider>
        ),
        withThemeByClassName({
            themes: { light: '', dark: 'dark' },
            defaultTheme: 'light',
        }),
    ],
});

// Storybook reads the preview from the default export, as Vite reads vite.config.js;
// stories import the named one.
export default preview;
