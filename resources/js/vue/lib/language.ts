const langGlobs = import.meta.glob('/lang/*.json', {
    eager: true,
}) as Record<string, { default: any }>;

/**
 * Resolve and merge JSON + PHP language files for i18n.
 *
 * Always merges `php_{lang}.json` into `{lang}.json` directly, rather than
 * relying on laravel-vue-i18n's hasPhpTranslations detection (which can fail
 * in newer Vite versions where process.env is not available in browser bundles).
 * PHP translations take precedence over JSON translations.
 */
const resolveLang = (lang: string): Record<string, any> => {
    const jsonData = langGlobs[`/lang/${lang}.json`]?.default ?? {};
    const phpData = langGlobs[`/lang/php_${lang}.json`]?.default ?? {};
    return { ...jsonData, ...phpData };
};

/**
 * Resolve and load a language JSON file for i18n.
 *
 * @param lang The language code to resolve (e.g., 'en', 'fr').
 * @returns  The language JSON object.
 */
export const resolveLanguage = (lang: string) => resolveLang(lang);

/**
 * Resolve and load a language JSON file for i18n SSR.
 *
 * @param lang The language code to resolve (e.g., 'en', 'fr').
 * @returns  The language JSON object.
 */
export const resolveLanguageForSsr = (lang: string) => resolveLang(lang);
