import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { readSettings } from '../settings.ts';

let root: string;

beforeEach(() => {
    root = fs.mkdtempSync(path.join(os.tmpdir(), 'saucebase-'));
});

afterEach(() => {
    fs.rmSync(root, { recursive: true, force: true });
});

describe('readSettings()', () => {
    it("reads the app's saucebase.json", () => {
        fs.writeFileSync(
            path.join(root, 'saucebase.json'),
            '{"contributor": true, "ssr": false}',
        );

        expect(readSettings(root)).toEqual({ contributor: true, ssr: false });
    });

    it('is empty for an app without one', () => {
        expect(readSettings(root)).toEqual({});
    });
});
