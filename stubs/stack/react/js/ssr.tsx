import App from '@/components/App';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { siteTitle } from '@saucebase/core/lib/settings';
import { I18nProvider } from '@saucebase/react/i18n';
import {
    registerAppPages,
    resolveModularPageComponent,
} from '@saucebase/react/lib/pages';
import ReactDOMServer from 'react-dom/server';
import { registerNavigationDefaults } from './lib/navigation';

registerNavigationDefaults();

registerAppPages(import.meta.glob<ResolvedComponent>('./pages/**/*.tsx'));

createServer((page) => {
    return createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: siteTitle,
        resolve: resolveModularPageComponent,
        setup({
            App: InertiaApp,
            props,
        }: {
            App: React.ComponentType<any>;
            props: Record<string, any>;
        }) {
            const locale = (page.props?.locale as string) || 'en';

            return (
                <I18nProvider initialLocale={locale}>
                    <App>
                        <InertiaApp {...props} />
                    </App>
                </I18nProvider>
            );
        },
    });
});
