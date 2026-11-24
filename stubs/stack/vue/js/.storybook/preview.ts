import '@css/app.css';
import { App, router, usePage } from '@inertiajs/vue3';
import addonA11y from '@storybook/addon-a11y';
import addonDocs from '@storybook/addon-docs';
import addonThemes, { withThemeByClassName } from '@storybook/addon-themes';
import addonVitest from '@storybook/addon-vitest';
import { action } from 'storybook/actions';
import { create } from 'storybook/theming';
import addonPseudoStates from 'storybook-addon-pseudo-states';
import addonTagBadges from 'storybook-addon-tag-badges';
import { definePreview, setup } from '@storybook/vue3-vite';
import {
    defineComponent,
    h,
    inject,
    provide,
    type DefineComponent,
    type InjectionKey,
    type Component,
} from 'vue';
import { registerNavigationDefaults } from '@/lib/navigation';
import { resolveLanguage } from '@saucebase/vue/lib/language';
import { i18nVue } from 'laravel-vue-i18n';
import { page } from './page';

// Ziggy's route(): Storybook has no routes, so every name exists, none is current, and a
// name is its own URL.
globalThis.route = ((name?: string) =>
    name === undefined
        ? { current: () => false, has: () => true }
        : `/${name}`) as typeof route;

// What app.ts gives every template: $page (createInertiaApp; Inertia keeps the plugin
// that adds it to itself), route() (ZiggyVue) and $t(). Storybook runs this on every
// render of the same app, so $page must be redefinable.
setup((app) => {
    const page = usePage();
    Object.defineProperty(app.config.globalProperties, '$page', {
        get: () => page,
        configurable: true,
    });
    app.config.globalProperties.route = route;
    app.use(i18nVue, { lang: 'en', resolve: resolveLanguage });
});

// A story has no server behind it: a visit would load Storybook itself into the frame.
// Cancel every Inertia visit and show it in the Actions panel instead.
router.on('before', (event) => {
    const { method, url } = event.detail.visit;
    action('inertia visit')(method.toUpperCase(), url.pathname + url.search);

    return false;
});

// What app.ts registers before the first render: the navigation's icons and actions.
registerNavigationDefaults();

// Inertia keeps one current page component for every App, so a docs page with several
// stories would show the last one in each. Every App renders this same component, which
// draws the story its own decorator provided. Page props are shared the same way: stories
// that differ only in `inertia` set `docs: { story: { inline: false } }`.
const STORY: InjectionKey<() => Component> = Symbol('story');
const Story = defineComponent({
    // Inertia passes the page props: they are not the story's attributes.
    inheritAttrs: false,
    setup: () => {
        const story = inject(STORY)!;

        return () => h(story());
    },
}) as DefineComponent;

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
        // Every story renders inside Inertia's App, so usePage() works. A story sets its
        // own page props with `parameters: { inertia: { ... } }`.
        (story, { parameters }) => ({
            setup: () => {
                provide(STORY, story);

                return () =>
                    h(App, {
                        initialPage: page(parameters.inertia),
                        initialComponent: Story,
                        resolveComponent: () => Story,
                    });
            },
        }),
        // The app's theme selector (in the user menu) applies the stored `appearance`, or
        // the device's theme when there is none. Store the toolbar's, so both agree.
        (story, { globals }) => {
            localStorage.setItem('appearance', globals.theme || 'light');

            return story();
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
