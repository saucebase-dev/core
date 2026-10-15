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

    // The page data is rewritten on its way to the browser, so the names change without a
    // settings write that would leak into the specs running beside this one.
    const names = ['Acme Shop', 'Saucebase', 'Gypsy Query'];

    for (const siteNameShown of names) {
        for (const width of [390, 820, 1600]) {
            test(`shows "${siteNameShown}" whole inside the footer at ${width}px`, async ({
                page,
            }) => {
                await page.route(
                    (url) => url.pathname === '/',
                    async (route) => {
                        const response = await route.fetch();
                        // The page data is embedded either escaped or as raw JSON.
                        const body = (await response.text())
                            .replace(
                                /(&quot;site_name&quot;:&quot;)[^&]*(&quot;)/g,
                                `$1${siteNameShown}$2`,
                            )
                            .replace(
                                /("site_name":")[^"]*(")/g,
                                `$1${siteNameShown}$2`,
                            );
                        await route.fulfill({ response, body });
                    },
                );
                await page.setViewportSize({ width, height: 900 });
                await page.goto('/');
                // The size is worked out for the monospace font, so measure once it has loaded.
                await page.evaluate(() => document.fonts.ready);

                const name = page
                    .getByTestId('footer-watermark')
                    .locator('span');
                await expect(name).toHaveText(siteNameShown);

                const footer = await page.locator('footer').boundingBox();
                const box = await name.boundingBox();

                // The footer hides what overflows it, so a name past any edge is cut.
                expect(box!.x).toBeGreaterThanOrEqual(footer!.x);
                expect(box!.x + box!.width).toBeLessThanOrEqual(
                    footer!.x + footer!.width,
                );
                expect(box!.y).toBeGreaterThanOrEqual(footer!.y);

                // The box runs past the letters, so the bottom is measured from the baseline:
                // room for letters that reach below it (g, j, p, q, y), and none otherwise.
                const roomBelowBaseline = await name.evaluate((element) => {
                    // A zero-size inline box sits on the baseline.
                    const marker = document.createElement('span');
                    marker.style.display = 'inline-block';
                    element.append(marker);
                    const baseline = marker.getBoundingClientRect().bottom;
                    marker.remove();

                    return (
                        (element.closest('footer')!.getBoundingClientRect()
                            .bottom -
                            baseline) /
                        Number.parseFloat(getComputedStyle(element).fontSize)
                    );
                });

                if (/[gjpqy]/.test(siteNameShown)) {
                    expect(roomBelowBaseline).toBeGreaterThanOrEqual(0.25);
                } else {
                    expect(roomBelowBaseline).toBeGreaterThanOrEqual(0);
                    expect(roomBelowBaseline).toBeLessThan(0.1);
                }

                // A name that wrapped would stand two lines tall.
                const fontSize = await name.evaluate((element) =>
                    Number.parseFloat(getComputedStyle(element).fontSize),
                );
                expect(box!.height).toBeLessThan(fontSize * 2);
            });
        }
    }
});
