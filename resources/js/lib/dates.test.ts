import { describe, expect, it } from 'vitest';
import { formatDate } from './dates';

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
