import App from '@/components/App';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { ModalStackProvider } from '@inertiaui/modal-react';
import { siteTitle } from '@saucebase/core/lib/settings';
import { ziggyForServer } from '@saucebase/core/lib/ziggy';
import { I18nProvider } from '@saucebase/react/i18n';
import {
    registerAppPages,
    resolveModularPageComponent,
} from '@saucebase/react/lib/pages';
import ReactDOMServer from 'react-dom/server';
import { route as ziggyRoute } from 'ziggy-js';
import { registerNavigationDefaults } from './lib/navigation';

registerNavigationDefaults();

registerAppPages(import.meta.glob<ResolvedComponent>('./pages/**/*.tsx'));

export default createServer((page) => {
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

            // The browser gets `route()` from the page's own script; the server has
            // to be handed this request's routes. Set here, straight before the
            // synchronous render, so one request never reads another's.
            (globalThis as any).route = (
                name?: string,
                params?: any,
                absolute?: boolean,
            ) =>
                ziggyRoute(
                    name as any,
                    params,
                    absolute,
                    ziggyForServer(page.props.ziggy as any) as any,
                );

            return (
                <I18nProvider initialLocale={locale}>
                    <ModalStackProvider>
                        <App>
                            <InertiaApp {...props} />
                        </App>
                    </ModalStackProvider>
                </I18nProvider>
            );
        },
    });
});
