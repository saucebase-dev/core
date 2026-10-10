import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import {
    collectModuleLangPaths,
    collectModulePlaywrightConfigs,
    loadEnabledModuleNames,
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
        "export default { name: 'auth.setup', testMatch: /x/ };",
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

    it('builds a Playwright project per module with its setups', async () => {
        const { projects, setups } =
            await collectModulePlaywrightConfigs(root);

        expect(projects).toContainEqual({
            name: '@auth',
            testDir: 'modules/auth/tests/e2e',
            dependencies: ['database.setup', 'auth.setup'],
        });
        expect(projects).toContainEqual({
            name: '@blog',
            testDir: 'modules/blog/tests/e2e',
            dependencies: ['database.setup'],
        });
        expect(setups).toEqual([
            expect.objectContaining({
                name: 'auth.setup',
                testDir: 'modules/auth/tests/e2e',
            }),
        ]);
    });
});
