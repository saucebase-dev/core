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

/** The override layer of a template workspace: app files replace core's UI, per framework. */
describe('saucebase() overrides', () => {
    function overrides(): any {
        return saucebase({
            framework: 'vue',
            entry: 'resources/js/app.ts',
            requires: {},
        }).find((p: any) => p?.name === 'saucebase:overrides') as any;
    }

    function write(file: string, contents: string): string {
        const full = path.join(root, file);
        fs.mkdirSync(path.dirname(full), { recursive: true });
        fs.writeFileSync(full, contents);

        return full;
    }

    const core = 'vendor/saucebase/core/stubs/stack/vue/js/pages/Index.vue';

    it("serves the app's file in place of core's", () => {
        write('saucebase.json', '{"contributor": true}');
        const corePage = write(core, 'core');
        write('resources/js/vue/pages/Index.vue', 'template');

        expect(overrides().load.call({ addWatchFile() {} }, corePage)).toBe(
            'template',
        );
        // Vite asks for the same file with a query for Vue's style and script parts.
        expect(
            overrides().load.call(
                { addWatchFile() {} },
                `${corePage}?vue&type=style&index=0`,
            ),
        ).toBeNull();
    });

    it("keeps core's file when the app has none", () => {
        write('saucebase.json', '{"contributor": true}');
        const corePage = write(core, 'core');

        expect(
            overrides().load.call({ addWatchFile() {} }, corePage),
        ).toBeNull();
    });

    it("ignores the other framework's overrides", () => {
        write('saucebase.json', '{"contributor": true}');
        const corePage = write(core, 'core');
        write('resources/js/react/pages/Index.vue', 'react');

        expect(
            overrides().load.call({ addWatchFile() {} }, corePage),
        ).toBeNull();
    });

    it('is off in an installed app, which owns its copy of the UI', () => {
        write('saucebase.json', '{}');
        const corePage = write(core, 'core');
        write('resources/js/vue/pages/Index.vue', 'template');

        expect(overrides()).toBeUndefined();
    });
});

/**
 * `@/` in a template workspace: the app's own UI first, then core's, else an error.
 * Vite's alias has already turned `@/x` into core's path when this runs.
 */
describe('saucebase() app-first @/', () => {
    const stack = 'vendor/saucebase/core/stubs/stack/vue/js';

    function write(file: string, contents = ''): string {
        const full = path.join(root, file);
        fs.mkdirSync(path.dirname(full), { recursive: true });
        fs.writeFileSync(full, contents);

        return full;
    }

    /** Vite's own resolution, reduced to what matters here: a file or a folder's index.ts. */
    const context = {
        resolve: async (id: string) =>
            [id, `${id}/index.ts`]
                .filter(
                    (file) => fs.existsSync(file) && fs.statSync(file).isFile(),
                )
                .map((file) => ({ id: file }))[0] ?? null,
        error: (message: string) => {
            throw new Error(message);
        },
    };

    async function resolve(file: string, importer = 'resources/js/app.ts') {
        const plugin = saucebase({
            framework: 'vue',
            entry: 'resources/js/app.ts',
            requires: {},
        }).find((p: any) => p?.name === 'saucebase:overrides') as any;

        const result = await plugin.resolveId.call(
            context,
            path.join(root, stack, file),
            path.join(root, importer),
            {},
        );

        return result?.id ? path.relative(root, result.id) : result;
    }

    beforeEach(() => {
        write('saucebase.json', '{"contributor": true, "template": "uptime"}');
    });

    it('finds a file only the app has', async () => {
        write('resources/js/vue/components/ui/select/index.ts');

        expect(await resolve('components/ui/select')).toBe(
            'resources/js/vue/components/ui/select/index.ts',
        );
    });

    it("leaves a file core has to core's path, which the override then fills", async () => {
        write(`${stack}/components/ui/button/index.ts`);
        write('resources/js/vue/components/ui/button/index.ts');

        expect(await resolve('components/ui/button')).toBeNull();
    });

    it("resolves an override's relative imports beside the override", async () => {
        const core = write(`${stack}/components/ui/button/index.ts`);
        write('resources/js/vue/components/ui/button/index.ts');
        write('resources/js/vue/components/ui/button/Fancy.vue');
        const plugin = saucebase({
            framework: 'vue',
            entry: 'resources/js/app.ts',
            requires: {},
        }).find((p: any) => p?.name === 'saucebase:overrides') as any;

        const result = await plugin.resolveId.call(
            context,
            './Fancy.vue',
            core,
            {},
        );

        expect(path.relative(root, result.id)).toBe(
            'resources/js/vue/components/ui/button/Fancy.vue',
        );
    });

    it("leaves a relative import core's own file has to Vite", async () => {
        const core = write(`${stack}/components/ui/button/index.ts`);
        write(`${stack}/components/ui/button/Button.vue`);
        const plugin = saucebase({
            framework: 'vue',
            entry: 'resources/js/app.ts',
            requires: {},
        }).find((p: any) => p?.name === 'saucebase:overrides') as any;

        expect(
            await plugin.resolveId.call(context, './Button.vue', core, {}),
        ).toBeNull();
    });

    it('leaves a file neither has unresolved, so Vite reports it', async () => {
        expect(await resolve('components/ui/nope')).toBeNull();
    });

    it("lets the template's own module use the app's files", async () => {
        write('resources/js/vue/components/ui/select/index.ts');

        expect(
            await resolve(
                'components/ui/select',
                'modules/uptime/resources/js/vue/pages/Monitors.vue',
            ),
        ).toBe('resources/js/vue/components/ui/select/index.ts');
    });

    it("stops another module from using a file only this app has: customers' apps won't have it", async () => {
        write('resources/js/vue/components/ui/select/index.ts');

        await expect(
            resolve(
                'components/ui/select',
                'modules/billing/resources/js/vue/pages/Settings.vue',
            ),
        ).rejects.toThrow(/billing.*components\/ui\/select/);
    });

    it('is off outside a template workspace: saucebase-dev builds core itself', async () => {
        write('saucebase.json', '{"contributor": true}');
        write('resources/js/vue/components/ui/select/index.ts');

        expect(await resolve('components/ui/select')).toBeNull();
    });

    it('lists what the app replaces and adds, so nothing changes silently', () => {
        write(`${stack}/pages/Index.vue`);
        write('resources/js/vue/pages/Index.vue');
        write('resources/js/vue/components/ui/select/index.ts');
        const info = vi.fn();

        const plugin = saucebase({
            framework: 'vue',
            entry: 'resources/js/app.ts',
            requires: {},
        }).find((p: any) => p?.name === 'saucebase:overrides') as any;
        plugin.configResolved({ logger: { info } });

        expect(info).toHaveBeenCalledWith(
            expect.stringMatching(/replaces core: pages\/Index\.vue/),
        );
        expect(info).toHaveBeenCalledWith(
            expect.stringMatching(/adds: components\/ui\/select\/index\.ts/),
        );
    });
});
