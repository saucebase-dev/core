export interface ModuleSetup {
    /**
     * Runs before the first render, on the server too: register components,
     * icons and actions here, and leave browser-only work to `afterMount`.
     */
    setup?: () => void | Promise<void>;
    afterMount?: () => void | Promise<void>;
}

export function discoverModuleSetups() {
    return import.meta.glob<ModuleSetup>(
        // Installed (flattened) modules, then a contributor checkout's react/ folder.
        [
            '/modules/*/resources/js/app.tsx',
            '/modules/*/resources/js/react/app.tsx',
        ],
        { eager: true },
    );
}

export async function executeModuleSetups(
    moduleSetups: Record<string, ModuleSetup>,
): Promise<void> {
    for (const module of Object.values(moduleSetups)) {
        if (module.setup) {
            await module.setup();
        }
    }
}

export async function executeAfterMountCallbacks(
    moduleSetups: Record<string, ModuleSetup>,
): Promise<void> {
    for (const module of Object.values(moduleSetups)) {
        if (module.afterMount) {
            await module.afterMount();
        }
    }
}
