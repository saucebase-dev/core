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
export async function collectModulePlaywrightConfigs(
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

        if (await exists(configFile)) {
            const config = await import(pathToFileURL(configFile).href);
            const entries: PlaywrightProject[] = [config.default]
                .flat()
                .filter(Boolean);

            for (const setup of entries) {
                if (!setup.name) {
                    console.warn(
                        `Module ${name}: a setup in playwright.config.ts has no 'name' and was skipped.`,
                    );
                    continue;
                }
                setupNames.push(setup.name);
                setups.push({ ...setup, testDir: setup.testDir ?? testDir });
            }
        }

        projects.push({
            name: `@${name}`,
            testDir,
            dependencies: ['database.setup', ...setupNames],
        });
    }

    return { projects, setups };
}

async function exists(file: string): Promise<boolean> {
    try {
        await fs.access(file);
        return true;
    } catch {
        return false;
    }
}
