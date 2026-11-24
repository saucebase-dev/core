import fs from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import type { ViteUserConfig } from 'vitest/config';

// Every story is a test: it must render, and its play function must pass.
// Here, beside .storybook/, because Storybook's "Run tests" button looks for it from there up.
// Storybook's CLI sets this for saucebase(); `npm run test:stories` runs outside it.
process.env.STORYBOOK = 'true';

// Resolved from the app, not from this file: in a contributor checkout this folder is a
// symlink into saucebase/core, which has no node_modules of its own.
const app = createRequire(path.resolve('package.json'));
const { storybookTest } = await import(
    app.resolve('@storybook/addon-vitest/vitest-plugin')
);
const { playwright } = await import(app.resolve('@vitest/browser-playwright'));

// This folder by its path in the app, as the Storybook CLI gets it: through the symlink,
// stories import from the app's node_modules. An installed app owns its own copy.
const configDir = path.resolve(
    fs.existsSync('resources/js/.storybook')
        ? 'resources/js/.storybook'
        : 'vendor/saucebase/core/stubs/stack/vue/js/.storybook',
);

const config: ViteUserConfig = {
    // The app's caches, wherever Vitest starts: from here they would create js/node_modules.
    cacheDir: path.resolve('node_modules/.vite'),
    // Storybook's button starts Vitest from this folder. In a contributor checkout that is
    // inside saucebase/core through a symlink, so Vite would only serve core's files: allow
    // the app (node_modules) and core's real folder (the stories).
    server: {
        fs: {
            allow: [
                process.cwd(),
                fs.realpathSync(path.resolve('vendor/saucebase/core')),
            ],
        },
    },
    test: {
        fsModuleCachePath: path.resolve('node_modules/.vitest-cache'),
        projects: [
            {
                extends: path.resolve('vite.config.js'),
                // The app root, wherever Vitest starts: Storybook's button starts it from here.
                root: process.cwd(),
                plugins: [storybookTest({ configDir })],
                test: {
                    name: 'storybook',
                    browser: {
                        enabled: true,
                        headless: true,
                        provider: playwright(),
                        instances: [{ browser: 'chromium' }],
                    },
                },
            },
        ],
    },
};

// Vitest reads its config from the default export.
export default config;
