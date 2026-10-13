import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { unmetRequirements, versionLine } from '../requirements.ts';

let root: string;

function install(name: string, version: string) {
    const dir = path.join(root, 'node_modules', name);
    fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(
        path.join(dir, 'package.json'),
        JSON.stringify({ name, version }),
    );
}

beforeEach(() => {
    root = fs.mkdtempSync(path.join(os.tmpdir(), 'saucebase-'));
});

afterEach(() => fs.rmSync(root, { recursive: true, force: true }));

describe('unmetRequirements', () => {
    it('accepts the minimum and anything newer', () => {
        install('vue', '3.5.0');
        install('@inertiajs/vue3', '4.1.2');

        expect(
            unmetRequirements(root, {
                vue: '3.5.0',
                '@inertiajs/vue3': '3.0.0',
            }),
        ).toEqual([]);
    });

    it('reports a package that is too old', () => {
        install('vue', '3.4.38');

        expect(unmetRequirements(root, { vue: '3.5.0' })).toEqual([
            'vue 3.4.38 is installed, but saucebase/core needs 3.5.0 or newer.',
        ]);
    });

    it('compares numbers, not text', () => {
        install('vite', '8.10.0');

        expect(unmetRequirements(root, { vite: '8.9.0' })).toEqual([]);
    });

    it('reports a package that is missing', () => {
        expect(unmetRequirements(root, { 'vue-sonner': '2.0.0' })).toEqual([
            'vue-sonner is not installed, but saucebase/core needs 2.0.0 or newer.',
        ]);
    });
});

describe('versionLine', () => {
    function composer(version: string) {
        const dir = path.join(root, 'vendor', 'composer');
        fs.mkdirSync(dir, { recursive: true });
        fs.writeFileSync(
            path.join(dir, 'installed.json'),
            JSON.stringify({
                packages: [
                    { name: 'laravel/framework', version: 'v13.0.0' },
                    { name: 'saucebase/core', version },
                ],
            }),
        );
    }

    it('names the installed core and framework versions', () => {
        composer('v1.8.2');
        install('react', '19.2.8');

        expect(versionLine(root, 'react')).toBe(
            'SAUCEBASE CORE v1.8.2  React v19.2.8',
        );
    });

    it('keeps a branch name as Composer gives it', () => {
        composer('dev-main');
        install('vue', '3.5.13');

        expect(versionLine(root, 'vue')).toBe(
            'SAUCEBASE CORE dev-main  Vue v3.5.13',
        );
    });

    it('leaves out a version whose file is half written', () => {
        composer('v1.8.2');
        fs.writeFileSync(
            path.join(root, 'vendor', 'composer', 'installed.json'),
            '{"packages": [',
        );

        expect(() => versionLine(root, 'vue')).not.toThrow();
    });

    it('paints the names when asked, and leaves the versions plain', () => {
        composer('v1.8.2');
        install('vue', '3.5.13');

        expect(versionLine(root, 'vue', true)).toBe(
            '\x1b[1;38;2;38;185;217mSAUCE\x1b[0m\x1b[1;38;2;122;123;232mBASE CORE\x1b[0m v1.8.2  \x1b[1;38;2;66;184;131mVue\x1b[0m v3.5.13',
        );
    });

    it('leaves out what it cannot find', () => {
        install('vue', '3.5.13');

        expect(versionLine(root, 'vue')).toBe('SAUCEBASE CORE  Vue v3.5.13');
    });
});
