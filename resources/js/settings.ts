import fs from 'node:fs';
import path from 'node:path';

/**
 * The app's `saucebase.json`: its framework, contributor mode, server rendering and
 * template. Read by the Vite preset and by Storybook's config, so both agree.
 */
export function readSettings(root: string): {
    contributor?: boolean;
    ssr?: boolean;
    template?: string;
} {
    const file = path.join(root, 'saucebase.json');

    return fs.existsSync(file)
        ? JSON.parse(fs.readFileSync(file, 'utf-8'))
        : {};
}
