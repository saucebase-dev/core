import preview from '@/.storybook/preview';
import { expect, screen, waitFor } from 'storybook/test';
import LanguageSelector from './LanguageSelector.vue';

const locales = { en: 'English', pt_BR: 'Português (Brasil)' };

/**
 * Lets the user switch the app's language. The languages come from the server (the
 * `locales` page prop); with only one, it renders nothing.
 *
 * Picking a language saves it on the server, so the stories open the menu but don't pick.
 */
const meta = preview.meta({
    title: 'App/LanguageSelector',
    component: LanguageSelector,
    // Slots the story fills itself: their controls would do nothing.
    argTypes: {
        trigger: { control: false },
        'submenu-trigger': { control: false },
    },
    parameters: {
        // Its stories differ only in page props, which one docs page shares.
        docs: { story: { inline: false, iframeHeight: '200px' } },
        inertia: { locale: 'en' },
    },
});
export default meta;

/** A dropdown with the current language's flag, as in the site header. */
export const Dropdown = meta.story({
    parameters: { inertia: { locales } },
    play: async ({ canvas, userEvent }) => {
        await userEvent.click(canvas.getByTestId('language-selector'));

        const option = await screen.findByRole('menuitem', {
            name: /Português/,
        });

        // The menu fades in.
        await waitFor(() => expect(option).toBeVisible());
    },
});

/** One language: nothing to choose, so nothing renders. */
export const OneLanguage = meta.story({
    // Not `locales` on the meta: Storybook merges parameters, so a story can't take one away.
    parameters: { inertia: { locales: { en: 'English' } } },
    play: async ({ canvas }) => {
        await expect(canvas.queryByTestId('language-selector')).toBeNull();
    },
});
