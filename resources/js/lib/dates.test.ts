import { describe, expect, it } from 'vitest';
import { formatDate, formatRelative } from './dates';

describe('formatDate', () => {
    it('formats in the language it is given', () => {
        expect(formatDate('2025-04-19', 'en')).toBe('Apr 19, 2025');
        expect(formatDate('2025-04-19', 'pt-BR')).toBe('19 de abr. de 2025');
    });

    it('keeps the calendar day whatever the time zone', () => {
        // Midnight UTC is still the 18th in the Americas; the date must not slip back.
        expect(formatDate('2025-04-19T00:00:00Z', 'en')).toBe('Apr 19, 2025');
        expect(formatDate('2025-04-19T23:59:59Z', 'en')).toBe('Apr 19, 2025');
    });

    it('accepts other formats', () => {
        expect(
            formatDate('2025-04-19', 'en', { year: 'numeric', month: 'long' }),
        ).toBe('April 2025');
    });

    it('returns an empty string when there is no date', () => {
        expect(formatDate(null, 'en')).toBe('');
        expect(formatDate(undefined, 'en')).toBe('');
        expect(formatDate('', 'en')).toBe('');
    });
});

describe('formatRelative', () => {
    const now = new Date('2026-10-28T12:00:00Z');
    const at = (seconds: number) =>
        new Date(now.getTime() + seconds * 1000).toISOString();
    const day = 86_400;

    it('says how long ago, or how long until, in the largest whole unit', () => {
        expect(formatRelative(at(-30), 'en', now)).toBe('now');
        expect(formatRelative(at(-5 * 60), 'en', now)).toBe('5 minutes ago');
        expect(formatRelative(at(-3 * 3600), 'en', now)).toBe('3 hours ago');
        expect(formatRelative(at(-day), 'en', now)).toBe('yesterday');
        expect(formatRelative(at(3 * day), 'en', now)).toBe('in 3 days');
        expect(formatRelative(at(21 * day), 'en', now)).toBe('in 3 weeks');
        expect(formatRelative(at(-62 * day), 'en', now)).toBe('2 months ago');
        expect(formatRelative(at(400 * day), 'en', now)).toBe('next year');
    });

    it('speaks the language it is given', () => {
        expect(formatRelative(at(-day), 'pt-BR', now)).toBe('ontem');
    });

    it('returns an empty string when there is no date', () => {
        expect(formatRelative(null, 'en', now)).toBe('');
        expect(formatRelative('', 'en', now)).toBe('');
    });
});
