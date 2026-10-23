import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import {
    collectModuleLangPaths,
    loadEnabledModuleNames,
    saucebaseProjects,
} from '../modules.ts';

let root: string;

function write(file: string, contents = '') {
    const target = path.join(root, file);
    fs.mkdirSync(path.dirname(target), { recursive: true });
    fs.writeFileSync(target, contents);
}

beforeEach(() => {
    root = fs.mkdtempSync(path.join(os.tmpdir(), 'saucebase-'));
    write('modules/auth/composer.json', '{}');
    write('modules/auth/lang/en.json', '{}');
    write(
        'modules/auth/playwright.config.ts',
        "export const playwright = { setups: [{ name: 'auth.setup', testMatch: /x/ }] };",
    );
    write('modules/blog/composer.json', '{}');
    write('modules/notes/README.md');
});

afterEach(() => fs.rmSync(root, { recursive: true, force: true }));

describe('modules', () => {
    it('finds directories with a composer.json', async () => {
        expect((await loadEnabledModuleNames(root)).sort()).toEqual([
            'auth',
            'blog',
        ]);
    });

    it('returns no modules when the directory is missing', async () => {
        expect(await loadEnabledModuleNames(path.join(root, 'nope'))).toEqual(
            [],
        );
    });

    it('collects the lang directories that exist', async () => {
        expect(await collectModuleLangPaths(root)).toEqual([
            'modules/auth/lang',
        ]);
    });

    it('builds the whole project list: setups first, every project per device', async () => {
        const projects = await saucebaseProjects({
            root,
            devices: { Chrome: { browserName: 'chromium' } },
            projects: [{ name: '@Core', testDir: './tests/e2e' }],
        });

        expect(projects.map((project) => project.name)).toEqual([
            'database.setup',
            'auth.setup',
            '@Core [Chrome]',
            '@auth [Chrome]',
            '@blog [Chrome]',
        ]);
        expect(projects[1]).toEqual(
            expect.objectContaining({ testDir: 'modules/auth/tests/e2e' }),
        );
        expect(projects[3]).toEqual({
            name: '@auth [Chrome]',
            testDir: 'modules/auth/tests/e2e',
            dependencies: ['database.setup', 'auth.setup'],
            use: { browserName: 'chromium' },
        });
    });

    it("applies a module's own project options, keeping core's name, folder and setups", async () => {
        write(
            'modules/blog/playwright.config.ts',
            "export const playwright = { project: { retries: 2, dependencies: ['x'], use: { locale: 'pt' } } };",
        );

        const projects = await saucebaseProjects({
            root,
            devices: { Chrome: { browserName: 'chromium' } },
        });

        expect(projects).toContainEqual({
            name: '@blog [Chrome]',
            testDir: 'modules/blog/tests/e2e',
            dependencies: ['database.setup'],
            retries: 2,
            use: { browserName: 'chromium', locale: 'pt' },
        });
    });

    it('runs run-alone projects after all others, one at a time', async () => {
        for (const name of ['blog', 'soon']) {
            write(`modules/${name}/composer.json`, '{}');
            write(
                `modules/${name}/playwright.config.ts`,
                'export const playwright = { runAlone: true };',
            );
        }

        const projects = await saucebaseProjects({
            root,
            devices: { Chrome: {}, Firefox: {} },
        });
        const dependencies = (name: string) =>
            projects.find((project) => project.name === name)?.dependencies;
        const normal = ['@auth [Chrome]', '@auth [Firefox]'];

        expect(projects.map((project) => project.name).slice(-4)).toEqual([
            '@blog [Chrome]',
            '@blog [Firefox]',
            '@soon [Chrome]',
            '@soon [Firefox]',
        ]);
        expect(dependencies('@blog [Chrome]')).toEqual([
            'database.setup',
            ...normal,
        ]);
        expect(dependencies('@blog [Firefox]')).toEqual([
            'database.setup',
            ...normal,
            '@blog [Chrome]',
        ]);
        expect(dependencies('@soon [Firefox]')).toEqual([
            'database.setup',
            ...normal,
            '@soon [Chrome]',
        ]);
        expect(projects.some((project) => 'runAlone' in project)).toBe(false);
    });
});
