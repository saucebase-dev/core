import { createRequire } from 'node:module';
import path from 'node:path';

// Storybook runs from this folder; the app's own vite.config.js (saucebase()) supplies
// the aliases, Tailwind and icons, and leaves out what only Laravel needs.

// Packages resolve from the app, not from this folder: in a contributor checkout it is a
// symlink into saucebase/core, which has no node_modules of its own.
const app = createRequire(path.resolve('package.json'));
const pkg = (name: string) => path.dirname(app.resolve(`${name}/package.json`));

export const framework = {
    name: pkg('@storybook/react-vite'),
    options: { builder: { viteConfigPath: 'vite.config.js' } },
};

export const stories = ['../**/*.mdx', '../**/*.stories.tsx'];

// The brand artwork the UI loads from /images, as the app serves it from public/.
export const staticDirs = [{ from: path.resolve('public/images'), to: '/images' }];

export const addons = [
    '@storybook/addon-docs',
    '@storybook/addon-themes',
    '@storybook/addon-a11y',
    '@storybook/addon-vitest',
].map(pkg);
