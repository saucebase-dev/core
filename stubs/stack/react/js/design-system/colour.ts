/** sRGB, 0–255 per channel. */
export type Rgb = [number, number, number];

/**
 * The colour a CSS value paints, in sRGB. A canvas does the conversion, so every syntax
 * the theme uses (oklch, hsl, color-mix) works. Browser only.
 */
export function toRgb(css: string): Rgb {
    const context = document
        .createElement('canvas')
        .getContext('2d', { willReadFrequently: true })!;
    context.fillStyle = css;
    context.fillRect(0, 0, 1, 1);
    const [r, g, b] = context.getImageData(0, 0, 1, 1).data;

    return [r, g, b];
}

/** WCAG 2 relative luminance. */
function luminance(rgb: Rgb): number {
    const [r, g, b] = rgb.map((channel) => {
        const value = channel / 255;

        return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

/** WCAG 2 contrast ratio between two colours: 1 (none) to 21 (black on white). */
export function contrast(a: Rgb, b: Rgb): number {
    const [lighter, darker] = [luminance(a), luminance(b)].sort((x, y) => y - x);

    return (lighter + 0.05) / (darker + 0.05);
}

/** WCAG AA: 4.5:1 for normal text, 3:1 for interface parts such as borders and focus rings. */
export function passes(ratio: number, kind: 'text' | 'ui'): boolean {
    return ratio >= (kind === 'text' ? 4.5 : 3);
}

export function hex(rgb: Rgb): string {
    return '#' + rgb.map((channel) => channel.toString(16).padStart(2, '0')).join('');
}
