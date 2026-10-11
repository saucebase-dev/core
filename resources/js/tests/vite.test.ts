import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { saucebase } from '../vite.ts';

// The build plugins are the app's dependencies; here only the preset's own config is under test.
vi.mock('@inertiajs/vite', () => ({ default: () => ({ name: 'inertia' }) }));
vi.mock('@tailwindcss/vite', () => ({
    default: () => ({ name: 'tailwindcss' }),
}));
vi.mock('laravel-vite-plugin', () => ({
    default: () => ({ name: 'laravel' }),
}));

let root: string;

function config(env: { isSsrBuild?: boolean } = {}): any {
    const plugin = saucebase({
        framework: 'vue',
        entry: 'resources/js/app.ts',
        requires: {},
    }).find((p: any) => p?.name === 'saucebase') as any;

    return plugin.config({ root }, env);
}

beforeEach(() => {
    root = fs.mkdtempSync(path.join(os.tmpdir(), 'saucebase-'));
});

afterEach(() => fs.rmSync(root, { recursive: true, force: true }));

describe('saucebase()', () => {
    it('points @ at the flat resources/js in an installed app', () => {
        const { resolve, define } = config();

        expect(resolve.alias['@']).toBe(path.join(root, 'resources/js'));
        expect(resolve.alias['@modules']).toBe(path.join(root, 'modules'));
        expect(define.__SAUCEBASE_CONTRIBUTOR__).toBe('false');
    });

    it("points @ at core's own copy of the UI in a contributor checkout", () => {
        fs.writeFileSync(
            path.join(root, 'saucebase.json'),
            '{"contributor": true}',
        );

        const { resolve, define } = config();

        expect(resolve.alias['@']).toBe(
            path.join(root, 'vendor/saucebase/core/stubs/stack/vue/js'),
        );
        expect(define.__SAUCEBASE_CONTRIBUTOR__).toBe('true');
    });

    it('skips the server build when saucebase.json turns server rendering off', () => {
        fs.writeFileSync(path.join(root, 'saucebase.json'), '{"ssr": false}');
        const exit = vi
            .spyOn(process, 'exit')
            .mockImplementation((() => {}) as never);
        const log = vi.spyOn(console, 'log').mockImplementation(() => {});

        config({ isSsrBuild: true });
        expect(exit).toHaveBeenCalledWith(0);

        exit.mockClear();
        config({ isSsrBuild: false });
        expect(exit).not.toHaveBeenCalled();

        exit.mockRestore();
        log.mockRestore();
    });

    it('writes module chunks and assets under the module name', () => {
        const { output } = config().build.rollupOptions;

        expect(
            output.chunkFileNames({
                facadeModuleId:
                    '/app/modules/blog/resources/js/pages/Index.vue',
                moduleIds: [],
            }),
        ).toBe('assets/blog/[name]-[hash].js');
        expect(
            output.chunkFileNames({
                facadeModuleId: '/app/resources/js/app.ts',
                moduleIds: [],
            }),
        ).toBe('assets/[name]-[hash].js');
        expect(
            output.assetFileNames({
                originalFileNames: ['/app/modules/auth/x.css'],
            }),
        ).toBe('assets/auth/[name]-[hash][extname]');
    });
});
