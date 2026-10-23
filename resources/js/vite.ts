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

    // The Inertia plugin turns app.ts's createInertiaApp() call into the server
    // renderer, so there is no ssr.ts. A contributor checkout's app.ts only imports
    // core's copy, so the server renders from that copy directly.
    const ssrEntry = contributor
        ? `${SAUCEBASE_STACK}/${framework}/js/${path.basename(entry)}`
        : entry;

    return [
        {
            name: 'saucebase',
            config: (userConfig, env) => {
                const root = path.resolve(userConfig.root ?? process.cwd());

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
        inertia({ ssr: ssrEntry }),
    ];
}

/** The app's `saucebase.json`: its framework, contributor mode and server rendering. */
function readSettings(root: string): { contributor?: boolean; ssr?: boolean } {
    const file = path.join(root, 'saucebase.json');

    return fs.existsSync(file)
        ? JSON.parse(fs.readFileSync(file, 'utf-8'))
        : {};
}

function moduleOf(file: string | undefined): string | null {
    return /\/modules\/([^/]+)\//.exec(file ?? '')?.[1] ?? null;
}
