import { expect, test } from '@playwright/test';

/**
 * The brand as a browser sees it.
 *
 * This spec must not write to settings. Playwright runs spec files in parallel workers
 * against one live application, so a write here changes what every other spec sees —
 * and `describe.serial` orders tests within a file, not across files.
 */
test.describe('Branding', () => {
    // The site name defaults to the application's name: `Laravel` in a new application.
    const siteName = process.env.APP_NAME ?? 'Laravel';

    const assets = [
        'logo-on-light',
        'logo-on-dark',
        'icon-on-light',
        'icon-on-dark',
    ];

    for (const asset of assets) {
        test(`ships ${asset}.svg`, async ({ request }) => {
            const response = await request.get(`/images/${asset}.svg`);

            // The settings default to these paths, so a 404 here is a broken image on
            // every page rather than a missing test fixture.
            expect(response.status(), `/images/${asset}.svg`).toBe(200);
            expect(response.headers()['content-type']).toContain(
                'image/svg+xml',
            );
        });
    }

    test('offers a light and a dark favicon', async ({ page }) => {
        await page.goto('/');

        await expect(
            page.locator('link[rel="icon"]:not([media])'),
        ).toHaveAttribute('href', '/images/icon-on-light.svg');

        // The server cannot pick: `appearance` may be `system`, which only the client
        // resolves. `media` hands the choice to the browser.
        await expect(
            page.locator(
                'link[rel="icon"][media="(prefers-color-scheme: dark)"]',
            ),
        ).toHaveAttribute('href', '/images/icon-on-dark.svg');
    });

    test('titles the page with the configured site name', async ({ page }) => {
        await page.goto('/');

        // No tagline is set by default, so the name stands alone. Asserting the default
        // value rather than writing one keeps this spec safe to run beside any other.
        await expect(page).toHaveTitle(siteName);
    });

    test('renders the wide lockup, swapping variants by colour scheme', async ({
        page,
    }) => {
        await page.emulateMedia({ colorScheme: 'light' });
        await page.goto('/');

        const onLight = page.locator('img[src="/images/logo-on-light.svg"]');
        const onDark = page.locator('img[src="/images/logo-on-dark.svg"]');

        // Both are in the DOM; CSS decides which one is painted. Resolving the theme in
        // JavaScript instead would flash on hydration, because `appearance: system` is
        // not something the server can answer.
        await expect(onLight).toBeVisible();
        await expect(onDark).toBeHidden();

        await page.emulateMedia({ colorScheme: 'dark' });
        await page.reload();

        await expect(onDark).toBeVisible();
        await expect(onLight).toBeHidden();
    });

    test('draws the name as artwork, not as text beside a mark', async ({
        page,
    }) => {
        await page.goto('/');

        // The lockup carries the name itself. An inline <svg> here would mean the old
        // component came back, and with it the hardcoded two-tone "sauce"/"base" split
        // that only ever rendered correctly for one brand.
        await expect(
            page.locator('img[src="/images/logo-on-light.svg"]'),
        ).toHaveAttribute('alt', siteName);
    });

    for (const width of [390, 820, 1600]) {
        test(`fits the large name inside the footer at ${width}px`, async ({
            page,
        }) => {
            await page.setViewportSize({ width, height: 900 });
            await page.goto('/');

            const footer = await page.locator('footer').boundingBox();
            const name = await page
                .getByTestId('footer-watermark')
                .boundingBox();

            // The footer hides what overflows it, so a name that reaches past an edge is cut.
            expect(name!.x).toBeGreaterThanOrEqual(footer!.x);
            expect(name!.x + name!.width).toBeLessThanOrEqual(
                footer!.x + footer!.width,
            );
            expect(name!.y).toBeGreaterThanOrEqual(footer!.y);
            expect(name!.y + name!.height).toBeLessThanOrEqual(
                footer!.y + footer!.height,
            );
        });
    }
});
