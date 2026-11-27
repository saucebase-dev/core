import fs from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

// Storybook runs from this folder; the app's own vite.config.js (saucebase()) supplies
// the aliases, Tailwind and icons, and leaves out what only Laravel needs.

// Packages resolve from the app, not from this folder: in a contributor checkout it is a
// symlink into saucebase/core, which has no node_modules of its own.
const app = createRequire(path.resolve('package.json'));
const pkg = (name: string) => path.dirname(app.resolve(`${name}/package.json`));

// saucebase.json says whether this is a contributor checkout; core's Vite preset reads it
// the same way. Imported from the app's vendor/, as vite.config.js imports the preset.
const { readSettings } = await import(
    pathToFileURL(
        path.resolve('vendor/saucebase/core/resources/js/settings.ts'),
    ).href
);
const { contributor = false } = readSettings(process.cwd());

export const framework = {
    name: pkg('@storybook/react-vite'),
    options: { builder: { viteConfigPath: 'vite.config.js' } },
};

// The design system's introduction first; Storybook orders entries as they are found.
// Then each module's own stories, under Modules/. Found from the app, so the same glob
// fits a contributor checkout (resources/js/react/) and an installed app (flattened).
// `directory` is relative to the config folder as Storybook got it: the Vitest plugin
// joins it onto that folder, so an absolute one would break `npm run test:stories`.
export const stories = (_: unknown, { configDir }: { configDir: string }) => [
    './design-system/Introduction.mdx',
    './design-system/*.mdx',
    '../**/*.stories.tsx',
    {
        directory: path.relative(configDir, path.resolve('modules')),
        files: '*/resources/js/**/*.stories.tsx',
        titlePrefix: 'Modules',
    },
];

// The brand artwork the UI loads from /images, as the app serves it from public/.
export const staticDirs = [
    { from: path.resolve('public/images'), to: '/images' },
];

export const addons = [
    '@storybook/addon-docs',
    '@storybook/addon-themes',
    '@storybook/addon-a11y',
    '@storybook/addon-vitest',
    // Serves the components, their props and stories to AI agents at /mcp.
    '@storybook/addon-mcp',
    'storybook-addon-pseudo-states',
    'storybook-addon-tag-badges',
].map(pkg);

// Props tables, "Show code" snippets and the /mcp docs come from docgen. Server docgen
// finds a component's tsconfig by walking up from its real path. In a contributor
// checkout that path is in saucebase/core, outside the app: `saucebase stack --dev` puts
// a tsconfig beside this folder for it. Without one, the builder's docgen reads the
// components instead (deprecated; gone in Storybook 12).
export const features = {
    docgenServer:
        fs.existsSync('resources/js/.storybook') ||
        fs.existsSync(path.resolve(import.meta.dirname, '../tsconfig.json')),
};

// Stories tagged `contributor` (the welcome page's parts) are for Saucebase's own team:
// an installed app's sidebar leaves them out. They still run as tests.
export const tags = { contributor: { hideFromSidebar: !contributor } };
