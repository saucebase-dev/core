import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import fs from 'node:fs';
import path from 'node:path';
import type { PluginOption } from 'vite';
import {
    REQUIREMENTS,
    type Requirements,
    unmetRequirements,
    versionLine,
} from './requirements.ts';
import { readSettings } from './settings.ts';

/** Where Composer installs this package's JavaScript, relative to the app root. */
const SAUCEBASE_JS = 'vendor/saucebase/core/resources/js';

/** Where the starter UI lives until it is published into an app's resources/js. */
const SAUCEBASE_STACK = 'vendor/saucebase/core/stubs/stack';

/**
 * The framework-neutral half of the Saucebase build. `vue/vite.ts` and `react/vite.ts`
 * call it and add their own plugins; apps use those, never this directly.
 */
export function saucebase({
    framework,
    entry,
    requires,
}: {
    framework: 'vue' | 'react';
    entry: string;
    /** The framework's own minimum npm versions. */
    requires: Requirements;
}): PluginOption[] {
    const settings = readSettings(process.cwd());
    const contributor = settings.contributor === true;
    // Storybook's CLI sets this. It runs Vite from its config folder (resources/js/.storybook)
    // and serves the UI itself: no Laravel entry to wire, no app.ts to server-render.
    const storybook = process.env.STORYBOOK === 'true';

    // The Inertia plugin turns app.ts's createInertiaApp() call into the server
    // renderer, so there is no ssr.ts. A contributor checkout's app.ts only imports
    // core's copy, so the server renders from that copy directly.
    const ssrEntry = contributor
        ? `${SAUCEBASE_STACK}/${framework}/js/${path.basename(entry)}`
        : entry;

    return [
        contributor && overrides(framework, settings.template),
        contributor && corePackages(),
        {
            name: 'saucebase',
            config: (userConfig, env) => {
                const root = storybook
                    ? process.cwd()
                    : path.resolve(userConfig.root ?? process.cwd());

                if (env?.isSsrBuild && settings.ssr === false) {
                    console.log(
                        '[saucebase] Server rendering is off in saucebase.json; skipping the server build.',
                    );
                    process.exit(0);
                }
                // A contributor checkout runs the UI straight from core; an installed app owns a copy.
                const appJs = contributor
                    ? `${SAUCEBASE_STACK}/${framework}/js`
                    : 'resources/js';

                return {
                    ...(storybook && { root }),
                    // Core is symlinked in a contributor checkout, and its own vendor/
                    // loops back on itself through testbench: 500,000+ watched files.
                    ...(contributor && {
                        server: { watch: { ignored: [unwatched(root)] } },
                    }),
                    define: {
                        __SAUCEBASE_CONTRIBUTOR__: JSON.stringify(contributor),
                    },
                    resolve: {
                        // Keep vendor/ paths when core is symlinked (a Composer path repository),
                        // so its imports resolve from the app's node_modules.
                        preserveSymlinks: true,
                        alias: {
                            [`@saucebase/${framework}`]: path.join(
                                root,
                                SAUCEBASE_JS,
                                framework,
                            ),
                            '@saucebase/core': path.join(root, SAUCEBASE_JS),
                            '@': path.join(root, appJs),
                            '@js': path.join(root, 'resources/js'),
                            '@css': path.join(root, 'resources/css'),
                            '@modules': path.join(root, 'modules'),
                            'ziggy-js': path.join(
                                root,
                                'vendor/tightenco/ziggy',
                            ),
                        },
                    },
                    build: {
                        rollupOptions: {
                            output: {
                                chunkFileNames: (chunk) => {
                                    const ids = chunk.facadeModuleId
                                        ? [chunk.facadeModuleId]
                                        : [...chunk.moduleIds];
                                    const module = ids.reduce<string | null>(
                                        (found, id) => found ?? moduleOf(id),
                                        null,
                                    );

                                    return module
                                        ? `assets/${module}/[name]-[hash].js`
                                        : 'assets/[name]-[hash].js';
                                },
                                assetFileNames: (asset) => {
                                    const module = moduleOf(
                                        asset.originalFileNames?.[0] ??
                                            asset.names?.[0] ??
                                            '',
                                    );

                                    return module
                                        ? `assets/${module}/[name]-[hash][extname]`
                                        : 'assets/[name]-[hash][extname]';
                                },
                            },
                        },
                    },
                };
            },
            configResolved: (config) => {
                for (const problem of unmetRequirements(config.root, {
                    ...REQUIREMENTS,
                    ...requires,
                })) {
                    config.logger.warn(`[saucebase] ${problem}`);
                }
            },
            // Vite's watcher never reaches into vendor/ on its own, so edits to core wouldn't hot-reload.
            configureServer: (server) => {
                server.watcher.add([
                    path.join(server.config.root, SAUCEBASE_JS),
                    path.join(server.config.root, SAUCEBASE_STACK),
                ]);

                // After Laravel's own lines, which it prints shortly after the server starts listening.
                server.httpServer?.once('listening', () => {
                    setTimeout(() => {
                        server.config.logger.info(
                            `\n  ${versionLine(server.config.root, framework, !process.env.NO_COLOR)}\n`,
                        );
                    }, 200);
                });
            },
        },
        tailwindcss(),
        !storybook &&
            laravel({
                input: [
                    'resources/css/app.css',
                    entry,
                    'resources/css/filament/admin/theme.css',
                ],
                // Without it the server build would bundle every input, CSS included.
                ssr: ssrEntry,
                refresh: true,
            }),
        !storybook && inertia({ ssr: ssrEntry }),
    ];
}

/**
 * A contributor checkout reads core's UI from vendor/; a template workspace overrides
 * parts of it from the app, one folder per framework, the way a template's stubs/
 * are laid out: resources/js/vue/pages/Index.vue replaces core's pages/Index.vue.
 *
 * In a template workspace `@/` also finds files only the app has, the way it does once
 * a template is installed: the app's UI first, then core's, else Vite's usual error.
 * Only the template's own module may use those files; any other module would break in
 * apps without this template.
 */
function overrides(
    framework: 'vue' | 'react',
    template: string | undefined,
): PluginOption {
    const root = process.cwd();
    const stack = path.join(root, SAUCEBASE_STACK, framework, 'js') + path.sep;
    const appFiles = path.join(root, 'resources/js', framework);
    const modules = path.join(root, 'modules') + path.sep;
    const override = (id: string) =>
        id.startsWith(stack) && !id.includes('?')
            ? path.join(appFiles, id.slice(stack.length))
            : null;

    return {
        name: 'saucebase:overrides',
        enforce: 'pre',
        // Shown at start, so the app's files never take over core's without a trace.
        configResolved(config) {
            if (!template || !fs.existsSync(appFiles)) {
                return;
            }
            const files = (
                fs.readdirSync(appFiles, { recursive: true }) as string[]
            )
                .filter((file) =>
                    fs.statSync(path.join(appFiles, file)).isFile(),
                )
                .map((file) => file.split(path.sep).join('/'))
                .sort((a, b) => a.localeCompare(b));
            const replaced = files.filter((file) =>
                fs.existsSync(stack + file),
            );
            const added = files.filter((file) => !replaced.includes(file));

            for (const [label, list] of [
                ['replaces core', replaced],
                ['adds', added],
            ] as const) {
                if (list.length > 0) {
                    config.logger.info(
                        `[saucebase] ${template} ${label}: ${list.join(', ')}`,
                    );
                }
            }
        },
        // Vite's alias has already turned `@/x` into core's path.
        async resolveId(id, importer, options) {
            // load() serves an override under core's path, so its relative imports start
            // beside core's file. One core has stays there; one only the app has is found
            // beside the override.
            const beside =
                template && importer && id.startsWith('.')
                    ? override(importer)
                    : null;

            if (importer && beside !== null && fs.existsSync(beside)) {
                const resolve = (target: string) =>
                    this.resolve(target, importer, {
                        ...options,
                        skipSelf: true,
                    });

                return (await resolve(path.resolve(path.dirname(importer), id)))
                    ? null
                    : resolve(path.resolve(path.dirname(beside), id));
            }

            const file = template ? override(id) : null;

            if (file === null) {
                return null;
            }
            const resolve = (target: string) =>
                this.resolve(target, importer, { ...options, skipSelf: true });

            // Core has it: keep core's path, and load() serves the app's copy if there is one.
            if (await resolve(id)) {
                return null;
            }
            const appOnly = await resolve(file);
            const module = importer?.startsWith(modules)
                ? importer.slice(modules.length).split(path.sep)[0]
                : null;

            if (appOnly && module !== null && module !== template) {
                this.error(
                    `modules/${module} imports @/${id.slice(stack.length)}, which only this app has. ` +
                        "Apps without the template won't have it: add it to core.",
                );
            }

            return appOnly;
        },
        load(id) {
            const file = override(id);

            if (file === null || !fs.existsSync(file)) {
                return null;
            }
            this.addWatchFile(file);

            return fs.readFileSync(file, 'utf-8');
        },
        // The page is known to Vite by core's path, so an edit to the override reloads it.
        handleHotUpdate({ file, server }) {
            if (!file.startsWith(appFiles + path.sep)) {
                return;
            }
            const replaced = path.join(stack, path.relative(appFiles, file));
            for (const module of server.moduleGraph.getModulesByFile(
                replaced,
            ) ?? []) {
                server.moduleGraph.invalidateModule(module);
            }
            server.ws.send({ type: 'full-reload' });

            return [];
        },
    };
}

/**
 * A contributor checkout reads core through vendor/ (`preserveSymlinks`), and the
 * installer's `stack --dev` links node_modules into core's stack for TypeScript. Vite
 * would find packages there under a second path: two copies of Vue and Inertia in one
 * build, and a module's usePage() reading the copy that has no page. Core's imports
 * resolve from the app root instead, as an installed app's do.
 */
function corePackages(): PluginOption {
    const root = process.cwd();
    const core = path.join(root, 'vendor/saucebase/core') + path.sep;
    const fromApp = path.join(root, 'package.json');

    return {
        name: 'saucebase:core-packages',
        enforce: 'pre',
        resolveId(id, importer, options) {
            // Relative, absolute (aliases are already applied) and virtual ids stay as they are.
            if (
                !importer?.startsWith(core) ||
                /^[./\\\0]/.test(id) ||
                id.includes(':')
            ) {
                return null;
            }

            return this.resolve(id, fromApp, { ...options, skipSelf: true });
        },
    };
}

/** PHP dependencies and runtime files, except core's frontend, which hot-reloads from vendor/. */
function unwatched(root: string): (file: string) => boolean {
    const inside = (file: string, folder: string) =>
        (file + path.sep).startsWith(path.join(root, folder) + path.sep);

    return (file) =>
        inside(file, 'storage') ||
        // A link to storage/app/public, which the watcher would follow.
        inside(file, 'public/storage') ||
        (inside(file, 'vendor') &&
            !inside(file, SAUCEBASE_JS) &&
            !inside(file, SAUCEBASE_STACK));
}

function moduleOf(file: string | undefined): string | null {
    return /\/modules\/([^/]+)\//.exec(file ?? '')?.[1] ?? null;
}
