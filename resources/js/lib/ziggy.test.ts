import { describe, expect, it } from 'vitest';
import { ziggyForServer } from './ziggy';

describe('ziggyForServer', () => {
    it('turns the location into a URL, which is what Ziggy reads the current route from', () => {
        const ziggy = ziggyForServer({
            url: 'https://example.test',
            location: 'https://example.test/blog?page=2',
        });

        expect(ziggy.location).toBeInstanceOf(URL);
        expect(ziggy.location.pathname).toBe('/blog');
        expect(ziggy.url).toBe('https://example.test');
    });
});
