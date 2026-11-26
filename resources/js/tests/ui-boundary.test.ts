import fs from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';

const STACK = path.resolve(import.meta.dirname, '../../../stubs/stack');

// App composites and app glue may know the app; the primitives under them may not.
const EXEMPT = [/\/ui\/saucebase\//, /\/ui\/sonner\/Wrapper\.vue$/];

function primitives(framework: 'vue' | 'react'): string[] {
    const ui = path.join(STACK, framework, 'js/components/ui');

    return (fs.readdirSync(ui, { recursive: true }) as string[])
        .map((file) => path.join(ui, file))
        .filter(
            (file) => /\.(vue|ts|tsx)$/.test(file) && !/\.stories\./.test(file),
        )
        .filter((file) => !EXEMPT.some((pattern) => pattern.test(file)));
}

describe.each(['vue', 'react'] as const)('%s ui/ primitives', (framework) => {
    // Props in, events out: they render in Storybook and in any page without an Inertia app.
    it('take their data as props, not from Inertia or Ziggy', () => {
        const coupled = primitives(framework).filter((file) =>
            /from ['"](@inertiajs\/|ziggy-js)/.test(
                fs.readFileSync(file, 'utf8'),
            ),
        );

        expect(coupled.map((file) => path.relative(STACK, file))).toEqual([]);
    });
});
