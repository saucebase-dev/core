/**
 * The `ziggy` page prop, ready for a server render. Laravel sends the location
 * as a string, but Ziggy reads the current route from a `URL` (in the browser,
 * `window.location`), so `route().current()` is always false without this.
 */
export function ziggyForServer<T extends { location: string }>(
    ziggy: T,
): Omit<T, 'location'> & { location: URL } {
    return { ...ziggy, location: new URL(ziggy.location) };
}
