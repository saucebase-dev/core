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

/**
 * What is installed, as one line for the dev server: `SAUCEBASE CORE v1.8.2  React v19.2.8`.
 * Read from Composer's and npm's own records, so it is never a hard-coded number.
 *
 * @param colors  Paint the names: Saucebase in its two brand colours, the framework in its own.
 */
export function versionLine(
    root: string,
    framework: 'vue' | 'react',
    colors = false,
): string {
    const read = (file: string) => {
        const full = path.join(root, file);

        return fs.existsSync(full)
            ? JSON.parse(fs.readFileSync(full, 'utf-8'))
            : null;
    };
    const paint = (rgb: string, text: string) =>
        colors ? `\x1b[1;38;2;${rgb}m${text}\x1b[0m` : text;

    const core: string | undefined = read(
        'vendor/composer/installed.json',
    )?.packages?.find(
        (installed: { name: string }) => installed.name === 'saucebase/core',
    )?.version;
    const library: string | undefined = read(
        `node_modules/${framework}/package.json`,
    )?.version;

    return [
        [paint('38;185;217', 'SAUCE') + paint('122;123;232', 'BASE CORE'), core]
            .filter(Boolean)
            .join(' '),
        [
            framework === 'vue'
                ? paint('66;184;131', 'Vue')
                : paint('97;218;251', 'React'),
            library && `v${library}`,
        ]
            .filter(Boolean)
            .join(' '),
    ].join('  ');
}

function isOlder(installed: string, minimum: string): boolean {
    const [a, b] = [installed, minimum].map((version) =>
        version.split('.').map((part) => Number.parseInt(part, 10) || 0),
    );

    for (let i = 0; i < 3; i++) {
        if (a[i] !== b[i]) {
            return a[i] < b[i];
        }
    }

    return false;
}
