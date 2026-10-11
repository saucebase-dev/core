import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { unmetRequirements } from '../requirements.ts';

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
