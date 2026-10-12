import { expect, test } from '@e2e/fixtures';

test.describe('{Module} index page', () => {
    test('responds successfully', async ({ page }) => {
        const response = await page.goto('/{module-}');

        expect(response, 'Expected a navigation response').toBeTruthy();
        expect(
            response?.ok(),
            'Expected a successful status code',
        ).toBeTruthy();
    });
});
