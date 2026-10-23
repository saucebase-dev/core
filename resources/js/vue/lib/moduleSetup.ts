import type { App } from 'vue';

export interface ModuleSetup {
    /**
     * Runs before the first render, on the server too: register components,
     * icons and actions here, and leave browser-only work to `afterMount`.
     */
    setup?: () => void | Promise<void>;
    afterMount?: (app?: App) => void | Promise<void>;
}

/**
 * Discovers every module's setup entry, eagerly. An installed app's modules are
 * flattened (`resources/js/app.ts`); a contributor checkout keeps both stacks
 * (`resources/js/vue/app.ts`). A module has one or the other, never both.
 */
export function discoverModuleSetups() {
    return import.meta.glob<ModuleSetup>(
        [
            '/modules/*/resources/js/app.ts',
            '/modules/*/resources/js/vue/app.ts',
        ],
        { eager: true },
    );
}

/**
 * Executes module setup functions before the first render
 * Collects afterMount callbacks for later execution
 *
 * @param moduleSetups - Module setup objects from discoverModuleSetups
 * @returns Array of afterMount callbacks to execute after mounting
 */
export async function executeModuleSetups(
    moduleSetups: Record<string, ModuleSetup>,
): Promise<((app?: App) => void | Promise<void>)[]> {
    const afterMountCallbacks: ((app?: App) => void | Promise<void>)[] = [];

    for (const module of Object.values(moduleSetups)) {
        if (module.setup) {
            await module.setup();
        }
        if (module.afterMount) {
            afterMountCallbacks.push(module.afterMount);
        }
    }

    return afterMountCallbacks;
}

/**
 * Executes all afterMount callbacks collected during setup
 *
 * @param callbacks - Array of afterMount callbacks
 */
export async function executeAfterMountCallbacks(
    callbacks: ((app?: App) => void | Promise<void>)[],
    app?: App,
): Promise<void> {
    for (const callback of callbacks) {
        await callback(app);
    }
}
