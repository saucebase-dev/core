import '@css/app.css';
import { App, router } from '@inertiajs/react';
import { ModalStackProvider } from '@inertiaui/modal-react';
import addonA11y from '@storybook/addon-a11y';
import addonDocs from '@storybook/addon-docs';
import addonThemes, { withThemeByClassName } from '@storybook/addon-themes';
import addonVitest from '@storybook/addon-vitest';
import { action } from 'storybook/actions';
import { create } from 'storybook/theming';
import addonPseudoStates from 'storybook-addon-pseudo-states';
import addonTagBadges from 'storybook-addon-tag-badges';
import { definePreview } from '@storybook/react-vite';
import { registerNavigationDefaults } from '@/lib/navigation';
import { I18nProvider } from '@saucebase/react/i18n';
import { page } from './page';

// Ziggy's route(): Storybook has no routes, so every name exists, none is current, and a
// name is its own URL.
globalThis.route = ((name?: string) =>
    name === undefined
        ? { current: () => false, has: () => true }
        : `/${name}`) as typeof route;

// A story has no server behind it: a visit would load Storybook itself into the frame.
// Cancel every Inertia visit and show it in the Actions panel instead.
router.on('before', (event) => {
    const { method, url } = event.detail.visit;
    action('inertia visit')(method.toUpperCase(), url.pathname + url.search);

    return false;
});

// What app.tsx registers before the first render: the navigation's icons and actions.
registerNavigationDefaults();

const preview = definePreview({
    addons: [
        addonDocs(),
        addonThemes(),
        addonA11y(),
        addonVitest(),
        // Forces :hover, :focus and the like from the toolbar, or per story with
        // `parameters: { pseudo: { focusVisible: true } }`.
        addonPseudoStates(),
        // Sidebar badges from tags: `new`, `beta`, `deprecated`, ...
        addonTagBadges(),
    ],
    // Every component gets a docs page: its description, live controls, props and stories.
    tags: ['autodocs'],
    parameters: {
        // Docs pages in the app's own fonts, not Storybook's.
        docs: {
            theme: create({
                base: 'light',
                fontBase: 'var(--font-sans)',
                fontCode: 'var(--font-mono)',
            }),
        },
    },
    decorators: [
        // Every story renders inside Inertia's App, so usePage() works, and inside the
        // translations and the modal stack (useAuth() needs it), as in app.tsx. A story sets
        // its own page props with `parameters: { inertia: { ... } }`.
        (Story, { parameters }) => (
            <I18nProvider initialLocale="en">
                <ModalStackProvider>
                    <App
                        initialPage={page(parameters.inertia)}
                        initialComponent={Story}
                        resolveComponent={() => Story}
                    />
                </ModalStackProvider>
            </I18nProvider>
        ),
        // The app's theme selector (in the user menu) applies the stored `appearance`, or
        // the device's theme when there is none. Store the toolbar's, so both agree.
        (Story, { globals }) => {
            localStorage.setItem('appearance', globals.theme || 'light');

            return <Story />;
        },
        withThemeByClassName({
            themes: { light: '', dark: 'dark' },
            defaultTheme: 'light',
        }),
    ],
});

// Storybook's convention, not production code: stories import the default export.
// Storybook reads `tags` without running this file, and only from this exact shape;
// a named export as well would hide them.
export default preview;
