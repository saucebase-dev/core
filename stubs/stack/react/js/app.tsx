import App from '@/components/App';
import { initializeTheme } from '@/hooks/useTheme';
import { setupProgress } from '@inertiajs/core';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import {
    initFromPageProps,
    ModalRoot,
    ModalStackProvider,
    putConfig,
} from '@inertiaui/modal-react';
import { initializeModals } from '@saucebase/core/lib/modal';
import { siteTitle } from '@saucebase/core/lib/settings';
import { ziggyForServer } from '@saucebase/core/lib/ziggy';
import { I18nProvider } from '@saucebase/react/i18n';
import { getGlobalComponents } from '@saucebase/react/lib/globalComponents';
import {
    discoverModuleSetups,
    executeAfterMountCallbacks,
    executeModuleSetups,
} from '@saucebase/react/lib/moduleSetup';
import {
    registerAppPages,
    resolveModularPageComponent,
} from '@saucebase/react/lib/pages';
import {
    cloneElement,
    useEffect,
    type ComponentType,
    type ReactElement,
    type ReactNode,
} from 'react';
import { route as ziggyRoute } from 'ziggy-js';
import { registerNavigationDefaults } from './lib/navigation';

const isServer = typeof window === 'undefined';

if (!isServer) {
    initializeTheme();
}

registerNavigationDefaults();

const moduleSetups = discoverModuleSetups();

initializeModals(putConfig);

registerAppPages(import.meta.glob<ResolvedComponent>('./pages/**/*.tsx'));

// Module setups only register components, icons and actions, so they run on the
// server too: both sides must render the same tree for the browser to hydrate it.
const setupsDone = executeModuleSetups(moduleSetups);

// Modules' afterMount() must land after the tree's own work. The outermost
// component's effect runs after every effect below it, and never on the server.
function AfterMount({ children }: { children: ReactNode }) {
    useEffect(() => {
        executeAfterMountCallbacks(moduleSetups).catch(console.error);
    }, []);

    return children;
}

function renderWithSlots({
    Component,
    props,
    key,
}: {
    Component: ComponentType<any>;
    props: Record<string, unknown>;
    key: string | number | null;
}) {
    return (
        <>
            {getGlobalComponents('app::page.before').map((TopComponent, i) => (
                <TopComponent key={i} />
            ))}
            <Component key={key} {...props} />
            {getGlobalComponents('app::page.after').map(
                (BottomComponent, i) => (
                    <BottomComponent key={i} />
                ),
            )}
            <ModalRoot />
        </>
    );
}

// Before the app mounts, not after as Inertia's own `progress` option does: a
// component that visits on mount (a `#settings/...` deep link) would otherwise
// start a bar that isn't set up yet.
if (!isServer) {
    setupProgress({
        color:
            getComputedStyle(document.documentElement)
                .getPropertyValue('--primary')
                .trim() || '#4B5563',
    });
}

// No `setup` callback: Inertia then hydrates server-rendered HTML itself
// (`hydrateRoot`) and the Vite plugin builds the server entry from this file.
createInertiaApp({
    title: siteTitle,
    resolve: async (name) => {
        await setupsDone;

        return resolveModularPageComponent(name);
    },
    withApp(app, { ssr, page }) {
        if (ssr) {
            const ziggy = ziggyForServer(page.props.ziggy as any);

            // The browser gets `route()` from the page's own script; the server has
            // to be handed this request's routes. Set here, straight before the
            // synchronous render, so one request never reads another's.
            (globalThis as any).route = (
                name?: string,
                params?: any,
                absolute?: boolean,
            ) => ziggyRoute(name as any, params, absolute, ziggy as any);
        } else {
            // What the modal package's `renderApp()` does, minus its fixed child
            // renderer, which would drop the app's global-component slots.
            initFromPageProps({
                // Inertia types the version as nullable, the package as optional.
                initialPage: { version: page.version ?? undefined },
            });
        }

        return (
            <AfterMount>
                <I18nProvider
                    initialLocale={(page.props?.locale as string) || 'en'}
                >
                    <ModalStackProvider>
                        <App>
                            {cloneElement(
                                app as ReactElement<{ children?: unknown }>,
                                {
                                    children: renderWithSlots,
                                },
                            )}
                        </App>
                    </ModalStackProvider>
                </I18nProvider>
            </AfterMount>
        );
    },
    // Set up below, before the app mounts.
    progress: false,
}).catch(console.error);
