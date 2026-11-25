import { describe, expect, it } from 'vitest';
import * as react from '../../../stubs/stack/react/js/.storybook/design-system/colour.ts';
import * as vue from '../../../stubs/stack/vue/js/.storybook/design-system/colour.ts';

// The design-system pages grade each token pair against WCAG AA with these. The colours
// below are fixed samples, not read from theme.css: changing the theme never fails this.
describe.each([
    ['vue', vue],
    ['react', react],
])('%s design-system colour', (_, colour) => {
    it('rates black on white 21 and a colour on itself 1', () => {
        expect(colour.contrast([0, 0, 0], [255, 255, 255])).toBeCloseTo(21, 1);
        expect(colour.contrast([239, 68, 68], [239, 68, 68])).toBe(1);
    });

    it("agrees with axe's ratios", () => {
        // Pairs axe (Storybook's accessibility check) rated: white on red-500, and
        // red-500 on a light grey.
        expect(colour.contrast([255, 255, 255], [239, 68, 68])).toBeCloseTo(
            3.76,
            2,
        );
        expect(colour.contrast([239, 68, 68], [232, 235, 237])).toBeCloseTo(
            3.14,
            2,
        );
    });

    it('is the same whichever colour comes first', () => {
        expect(colour.contrast([107, 114, 128], [232, 235, 237])).toBe(
            colour.contrast([232, 235, 237], [107, 114, 128]),
        );
    });

    it('passes AA at 4.5 for text and 3 for interface parts', () => {
        expect(colour.passes(4.5, 'text')).toBe(true);
        expect(colour.passes(4.49, 'text')).toBe(false);
        expect(colour.passes(3, 'ui')).toBe(true);
        expect(colour.passes(2.99, 'ui')).toBe(false);
    });

    it('writes a colour as hex', () => {
        expect(colour.hex([255, 107, 0])).toBe('#ff6b00');
    });
});
