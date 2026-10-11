import { usePage } from '@inertiajs/vue3';
import { loadLanguageAsync } from 'laravel-vue-i18n';
import { computed, readonly, ref, watch, type Ref } from 'vue';
import { useHttp } from './useHttp';

// One state per browser tab, so all components share the same locale.
let languageState: Ref<string> | null = null;
let isInitialized = false;

// The server process outlives a request: state kept here would hand one visitor's
// language to the next, so server rendering gets its own on every call.
const isServer = typeof window === 'undefined';

/**
 * Composable for managing application localization
 *
 * Provides reactive locale state from Inertia props and handles language switching.
 * The locale is sourced from the server session and automatically syncs across
 * page navigations.
 *
 * Uses singleton pattern to ensure all components share the same state.
 */
export const useLocalization = () => {
    const page = usePage();

    const initial = (page.props?.locale as string) || 'en';
    let language: Ref<string>;
    if (isServer) {
        language = ref(initial);
    } else {
        languageState ??= ref(initial);
        language = languageState;
    }

    // Get available locales mapping from Inertia props
    const locales = computed(
        () => (page.props?.locales as Record<string, string>) || {},
    );

    /**
     * Change the application language
     * Updates both the backend session and frontend i18n
     */
    const setLanguage = async (lang: string) => {
        const { post } = useHttp();
        let saved = false;

        // Update backend session; a failure is shown and the language stays as it was
        await post(route('locale', { locale: lang }), {
            onSuccess: () => (saved = true),
        });

        if (!saved) {
            return;
        }

        language.value = lang;

        // Load i18n translations for the new language
        await loadLanguageAsync(lang);
    };

    // Set up watch only once to avoid multiple watchers
    if (!isServer && !isInitialized) {
        isInitialized = true;

        // Sync language when page props change (on navigation)
        watch(
            () => page.props?.locale,
            (newLocale) => {
                if (newLocale && newLocale !== language.value) {
                    language.value = newLocale as string;
                    loadLanguageAsync(newLocale as string);
                }
            },
        );
    }

    return {
        language: readonly(language),
        locales,
        setLanguage,
    };
};
