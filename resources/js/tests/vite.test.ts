import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { saucebase } from '../vite.ts';

// The build plugins are the app's dependencies; here only the preset's own config is under test.
const inertia = vi.hoisted(() => vi.fn(() => ({ name: 'inertia' })));
vi.mock('@inertiajs/vite', () => ({ default: inertia }));
vi.mock('@tailwindcss/vite', () => ({
    default: () => ({ name: 'tailwindcss' }),
}));
const laravel = vi.hoisted(() => vi.fn(() => ({ name: 'laravel' })));
vi.mock('laravel-vite-plugin', () => ({ default: laravel }));

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
    // saucebase.json is read from where Vite runs, the app root.
    vi.spyOn(process, 'cwd').mockReturnValue(root);
});

afterEach(() => {
    vi.restoreAllMocks();
    fs.rmSync(root, { recursive: true, force: true });
});

describe('saucebase()', () => {
    it("stops a contributor's watcher at PHP dependencies and runtime files, but not core's frontend", () => {
        fs.writeFileSync(
            path.join(root, 'saucebase.json'),
            '{"contributor": true}',
        );
        const [ignored] = config().server.watch.ignored;

        for (const file of [
            'vendor/symfony/console/Application.php',
            'vendor/saucebase/core/vendor/orchestra/testbench-core/laravel/vendor',
            'vendor/saucebase/core/src/Foo.php',
            'storage/logs/laravel.log',
            'public/storage/avatars/photo.png',
        ]) {
            expect(ignored(path.join(root, file)), file).toBe(true);
        }

        for (const file of [
            // The folders themselves: ignoring one drops everything in it.
            'vendor/saucebase/core/resources/js',
            'vendor/saucebase/core/stubs/stack',
            'vendor/saucebase/core/resources/js/vite.ts',
            'vendor/saucebase/core/stubs/stack/vue/js/app.ts',
            'resources/js/vendor/foo.ts',
            'modules/billing/resources/js/vendor/foo.ts',
            'modules/billing/storage/foo.ts',
        ]) {
            expect(ignored(path.join(root, file)), file).toBe(false);
        }
    });

    it("leaves an installed app's watcher alone", () => {
        expect(config().server).toBeUndefined();
    });

    function build() {
        saucebase({
            framework: 'vue',
            entry: 'resources/js/app.ts',
            requires: {},
        });
    }

    it('builds the app CSS as its own entry and renders on the server from app.ts', () => {
        build();

        expect(laravel).toHaveBeenLastCalledWith(
            expect.objectContaining({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.ts',
                    'resources/css/filament/admin/theme.css',
                ],
                ssr: 'resources/js/app.ts',
            }),
        );
        expect(inertia).toHaveBeenLastCalledWith({
            ssr: 'resources/js/app.ts',
        });
    });

    it("renders on the server from core's app.ts in a contributor checkout", () => {
        fs.writeFileSync(
            path.join(root, 'saucebase.json'),
            '{"contributor": true}',
        );
        build();

        const stub = 'vendor/saucebase/core/stubs/stack/vue/js/app.ts';
        expect(laravel).toHaveBeenLastCalledWith(
            expect.objectContaining({ ssr: stub }),
        );
        expect(inertia).toHaveBeenLastCalledWith({ ssr: stub });
    });

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
