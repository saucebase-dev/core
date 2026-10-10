import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { saucebase } from '../vite.ts';

// The build plugins are the app's dependencies; here only the preset's own config is under test.
vi.mock('@inertiajs/vite', () => ({ default: () => ({ name: 'inertia' }) }));
vi.mock('@tailwindcss/vite', () => ({
    default: () => ({ name: 'tailwindcss' }),
}));
vi.mock('laravel-vite-plugin', () => ({
    default: () => ({ name: 'laravel' }),
}));

let root: string;

function config(): any {
    const plugin = saucebase({
        framework: 'vue',
        entry: 'resources/js/app.ts',
        requires: {},
    }).find((p: any) => p?.name === 'saucebase') as any;

    return plugin.config({ root });
}

beforeEach(() => {
    root = fs.mkdtempSync(path.join(os.tmpdir(), 'saucebase-'));
});

afterEach(() => fs.rmSync(root, { recursive: true, force: true }));

describe('saucebase()', () => {
    it('points @ at the flat resources/js in an installed app', () => {
        const { resolve, define } = config();

        expect(resolve.alias['@']).toBe(path.join(root, 'resources/js'));
        expect(resolve.alias['@modules']).toBe(path.join(root, 'modules'));
        expect(define.__SAUCEBASE_DEV__).toBe('false');
        expect(define.__SAUCEBASE_APP_JS__).toBe('"/resources/js"');
        expect(define.__SAUCEBASE_FRAMEWORK__).toBe('"vue"');
    });

    it("points @ at core's own copy of the UI in a contributor checkout", () => {
        fs.writeFileSync(path.join(root, 'frontend.json'), '{"dev": true}');

        const { resolve, define } = config();

        expect(resolve.alias['@']).toBe(
            path.join(root, 'vendor/saucebase/core/stubs/stack/vue/js'),
        );
        expect(define.__SAUCEBASE_DEV__).toBe('true');
        expect(define.__SAUCEBASE_APP_JS__).toBe(
            '"/vendor/saucebase/core/stubs/stack/vue/js"',
        );
    });

    it('writes module chunks and assets under the module name', () => {
        const { output } = config().build.rollupOptions;

        expect(
            output.chunkFileNames({
                facadeModuleId:
                    '/app/modules/blog/resources/js/pages/Index.vue',
                moduleIds: [],
            }),
        ).toBe('assets/blog/[name]-[hash].js');
        expect(
            output.chunkFileNames({
                facadeModuleId: '/app/resources/js/app.ts',
                moduleIds: [],
            }),
        ).toBe('assets/[name]-[hash].js');
        expect(
            output.assetFileNames({
                originalFileNames: ['/app/modules/auth/x.css'],
            }),
        ).toBe('assets/auth/[name]-[hash][extname]');
    });
});
