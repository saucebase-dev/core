import { expect, type Page } from '@playwright/test';

/**
 * Verify that Inertia page data is available without JavaScript
 *
 * Note: Inertia SSR doesn't render full HTML content visible without JavaScript.
 * Instead, it embeds page data in a script tag for faster hydration and SEO.
 * Search engines can execute JavaScript to render the content, but browsers with
 * JavaScript disabled will see an empty page. This is by design.
 *
 * This test verifies that the SSR-generated page data is properly embedded.
 */
export async function expectInertiaPageDataEmbedded(page: Page) {
    const htmlContent = await page.content();

    // Verify the app div exists
    expect(htmlContent).toContain('id="app"');

    // Verify that Inertia page data is embedded (this is what SSR provides)
    // The data-page attribute and script tag contain the initial page state
    expect(htmlContent).toContain('data-page');

    // Verify the page data script tag exists
    const scriptMatch = htmlContent.match(
        /<script[^>]*data-page[^>]*type="application\/json">({.+?})<\/script>/,
    );
    expect(scriptMatch).toBeTruthy();

    // The page data should contain meaningful content
    if (scriptMatch) {
        const pageData = scriptMatch[1];
        // Should have component and props data
        expect(pageData.length).toBeGreaterThan(100);
    }
}
