import { expect, test } from '@e2e/fixtures';

/**
 * The browser takes over the server's HTML instead of rebuilding it. A rebuild swaps
 * the whole tree for new elements, which restarts CSS animations and wastes a render.
 */
test('the browser hydrates the server-rendered page in place', async ({
    page,
}) => {
    await page.addInitScript(() => {
        const w = window as unknown as { replaced: number };
        w.replaced = 0;
        new MutationObserver((mutations) => {
            for (const m of mutations) {
                if ((m.target as Element).id === 'app') {
                    w.replaced += [...m.removedNodes].filter(
                        (node) => node instanceof Element,
                    ).length;
                }
            }
        }).observe(document, { childList: true, subtree: true });
    });

    const response = await page.goto('/');
    expect(await response!.text(), 'the page was server-rendered').toContain(
        'data-server-rendered="true"',
    );
    await page.waitForLoadState('networkidle');

    expect(
        await page.evaluate(
            () => (window as unknown as { replaced: number }).replaced,
        ),
    ).toBe(0);
});
