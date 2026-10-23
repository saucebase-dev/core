import fs from 'node:fs/promises';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

/**
 * Module discovery for the build and the E2E suite. A module is a directory under
 * `modules/` with a `composer.json`. Paths returned are relative to the app root.
 */

const MODULES_PATH = 'modules';

type PlaywrightProject = Record<string, unknown> & {
    name: string;
    testDir?: string;
};

/**
 * What a module's `playwright.config.ts` exports as `playwright`:
 * `export const playwright = { ... } satisfies ModulePlaywrightOptions;`
 * (`import type { ModulePlaywrightOptions } from '@saucebase/core/modules'`).
 */
export interface ModulePlaywrightOptions {
    /** Setup projects the module's tests depend on; each needs a `name`. */
    setups?: PlaywrightProject[];
    /**
     * The tests change state the whole app shares (a site-wide setting), so they
     * run after every other project, with nothing beside them. See saucebaseProjects().
     */
    runAlone?: boolean;
    /** Playwright options for the module's project (`use`, `timeout`, `retries`, ...). */
    project?: Record<string, unknown>;
}

export async function loadEnabledModuleNames(
    root = process.cwd(),
): Promise<string[]> {
    let entries;
    try {
        entries = await fs.readdir(path.join(root, MODULES_PATH), {
            withFileTypes: true,
        });
    } catch {
        return [];
    }

    const names: string[] = [];
    for (const entry of entries) {
        if (!entry.isDirectory() || entry.name.startsWith('.')) continue;
        if (
            await exists(
                path.join(root, MODULES_PATH, entry.name, 'composer.json'),
            )
        ) {
            names.push(entry.name);
        }
    }

    return names;
}

/**
 * The `lang/` directories of modules that have one, for `laravel-vue-i18n`.
 */
export async function collectModuleLangPaths(
    root = process.cwd(),
): Promise<string[]> {
    const langPaths: string[] = [];

    for (const name of await loadEnabledModuleNames(root)) {
        const langPath = path.join(MODULES_PATH, name, 'lang');
        if (await exists(path.join(root, langPath))) {
            langPaths.push(langPath);
        }
    }

    return langPaths;
}

/**
 * A Playwright project per module, named `@<module>`, plus the setup projects a module
 * exports by default from its `playwright.config.ts`.
 */
/** Seeds the app's database once, before every project. */
const DATABASE_SETUP: PlaywrightProject = {
    name: 'database.setup',
    testMatch: /database\.setup\.ts/,
};

/**
 * Every Playwright project the app runs, ready for `defineConfig({ projects })`:
 * `database.setup`, the modules' setups, then the app's own projects and one per
 * module, each repeated per device. Projects a module marks `runAlone` come last,
 * one at a time, after everything else has finished.
 *
 * `devices` maps a name to its settings (`{ 'Desktop Chrome': devices['Desktop Chrome'] }`):
 * the app passes them because core can't load Playwright itself.
 */
export async function saucebaseProjects({
    devices,
    projects = [],
    root = process.cwd(),
}: {
    devices: Record<string, Record<string, unknown>>;
    /** The app's own projects, such as its core tests. */
    projects?: PlaywrightProject[];
    root?: string;
}): Promise<PlaywrightProject[]> {
    const modules = await collectModulePlaywrightConfigs(root);
    const all: PlaywrightProject[] = [
        ...projects.map((project): PlaywrightProject => ({
            ...project,
            dependencies: [
                ...new Set([
                    DATABASE_SETUP.name,
                    ...((project.dependencies as string[]) ?? []),
                ]),
            ],
        })),
        ...modules.projects,
    ].flatMap((project) =>
        Object.entries(devices).map(([device, use]) => ({
            ...project,
            name: `${project.name} [${device}]`,
            use: { ...use, ...(project.use as Record<string, unknown>) },
        })),
    );

    const others = all.filter((project) => !project.runAlone);
    // Each waits for every other project and for the run-alone one before it, so
    // two never overlap: they change state the whole app shares.
    let previous: string[] = [];
    const alone = all
        .filter((project) => project.runAlone)
        .map(({ runAlone, ...project }) => {
            const dependencies = [
                ...(project.dependencies as string[]),
                ...others.map((other) => other.name),
                ...previous,
            ];
            previous = [project.name];

            return { ...project, dependencies };
        });

    return [DATABASE_SETUP, ...modules.setups, ...others, ...alone];
}

async function collectModulePlaywrightConfigs(
    root = process.cwd(),
): Promise<{ projects: PlaywrightProject[]; setups: PlaywrightProject[] }> {
    const projects: PlaywrightProject[] = [];
    const setups: PlaywrightProject[] = [];

    for (const name of await loadEnabledModuleNames(root)) {
        const testDir = path.join(MODULES_PATH, name, 'tests', 'e2e');
        const setupNames: string[] = [];
        const configFile = path.join(
            root,
            MODULES_PATH,
            name,
            'playwright.config.ts',
        );
        const options = await loadPlaywrightOptions(configFile);

        for (const setup of options.setups ?? []) {
            if (!setup.name) {
                console.warn(
                    `Module ${name}: a setup in playwright.config.ts has no 'name' and was skipped.`,
                );
                continue;
            }
            setupNames.push(setup.name);
            setups.push({ ...setup, testDir: setup.testDir ?? testDir });
        }

        projects.push({
            ...options.project,
            // Core's own: where the tests live and what must run before them.
            name: `@${name}`,
            testDir,
            dependencies: ['database.setup', ...setupNames],
            ...(options.runAlone && { runAlone: true }),
        });
    }

    return { projects, setups };
}

/** A module's `playwright` export; none when it has no `playwright.config.ts`. */
async function loadPlaywrightOptions(
    file: string,
): Promise<ModulePlaywrightOptions> {
    if (!(await exists(file))) {
        return {};
    }

    return (await import(pathToFileURL(file).href)).playwright ?? {};
}

async function exists(file: string): Promise<boolean> {
    try {
        await fs.access(file);
        return true;
    } catch {
        return false;
    }
}
