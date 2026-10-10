import fs from 'node:fs';
import path from 'node:path';

/**
 * Composer can't require npm packages, so this code trusts the app's `package.json`
 * to bring what it imports. The build checks the installed versions against these
 * minimums and warns, so a stale package shows up here and not as a runtime error.
 */
export type Requirements = Record<string, string>;

export const REQUIREMENTS: Requirements = {
    vite: '8.0.0',
    '@inertiajs/vite': '3.0.0',
    '@tailwindcss/vite': '4.0.0',
    'laravel-vite-plugin': '3.0.0',
};

/**
 * @returns One sentence per package that is missing or older than its minimum.
 */
export function unmetRequirements(
    root: string,
    requirements: Requirements,
): string[] {
    const problems: string[] = [];

    for (const [name, minimum] of Object.entries(requirements)) {
        const manifest = path.join(root, 'node_modules', name, 'package.json');

        if (!fs.existsSync(manifest)) {
            problems.push(
                `${name} is not installed, but saucebase/core needs ${minimum} or newer.`,
            );
            continue;
        }

        const installed: string = JSON.parse(
            fs.readFileSync(manifest, 'utf-8'),
        ).version;

        if (isOlder(installed, minimum)) {
            problems.push(
                `${name} ${installed} is installed, but saucebase/core needs ${minimum} or newer.`,
            );
        }
    }

    return problems;
}

function isOlder(installed: string, minimum: string): boolean {
    const [a, b] = [installed, minimum].map((version) =>
        version.split('.').map((part) => parseInt(part, 10) || 0),
    );

    for (let i = 0; i < 3; i++) {
        if (a[i] !== b[i]) {
            return a[i] < b[i];
        }
    }

    return false;
}
