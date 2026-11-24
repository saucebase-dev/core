import fs from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';

const PREVIEW = path.resolve(
    import.meta.dirname,
    '../../../stubs/stack/vue/js/.storybook/preview.ts',
);

// React follows once its Storybook catches up with Vue's.
describe('vue Storybook preview', () => {
    // Storybook reads the preview's tags without running it, and only from
    // `const preview = definePreview({...}); export default preview;`. Any other
    // export hides them, and every component silently loses its docs page.
    it('gives every component a docs page', () => {
        const source = fs.readFileSync(PREVIEW, 'utf8');

        expect(source).toMatch(/tags:\s*\['autodocs'\]/);
        expect(source).not.toMatch(/export (const|\{)/);
    });

    // Server docgen finds components by their real path. In a contributor checkout that
    // is saucebase/core, outside the app's tsconfig: no props table, and "Show code"
    // shows the story's source. There, the builder's docgen reads them instead.
    it('documents components in a contributor checkout too', () => {
        const source = fs.readFileSync(
            path.resolve(path.dirname(PREVIEW), 'main.ts'),
            'utf8',
        );

        expect(source).toContain(
            "docgenServer: fs.existsSync('resources/js/.storybook')",
        );
        // vue-docgen-api, the builder's default, can't follow imported prop types
        // (SidebarProps): their controls would be JSON boxes, or missing.
        expect(source).toContain("docgen: 'vue-component-meta'");
    });
});
