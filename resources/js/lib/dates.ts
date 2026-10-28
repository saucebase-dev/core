const DEFAULT_FORMAT: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
};

/**
 * A day, in the app's language, as the same text on the server and in the
 * browser.
 *
 * Pages render on the server first, and the server and the reader rarely share a
 * time zone, so formatting in the reader's zone prints one day in the HTML and
 * another once the page hydrates. UTC is the one zone both agree on. It also
 * keeps a date-only value such as `2025-04-19`, which the browser reads as UTC
 * midnight, from sliding back a day for anyone west of Greenwich.
 */
export function formatDate(
    value: string | Date | null | undefined,
    locale: string | undefined,
    options: Intl.DateTimeFormatOptions = DEFAULT_FORMAT,
): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleDateString(locale, {
        ...options,
        timeZone: 'UTC',
    });
}

/**
 * A moment, with its time of day, in the reader's own zone.
 *
 * Only for pages that never render on the server: a time of day in UTC would be
 * wrong for almost everyone, and one in the reader's zone cannot match the
 * server's copy.
 */
export function formatDateTime(
    value: string | Date | null | undefined,
    locale: string | undefined,
): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString(locale, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const RELATIVE_UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 365 * 86_400],
    ['month', 30 * 86_400],
    ['week', 7 * 86_400],
    ['day', 86_400],
    ['hour', 3_600],
    ['minute', 60],
];

/**
 * How long ago, or how long until, in the app's language: "yesterday", "in 3 weeks",
 * "2 months ago". Under a minute is "now".
 *
 * Only for pages that never render on the server, like `formatDateTime()`: the
 * server's "now" is not the reader's, so the text would change on hydration.
 */
export function formatRelative(
    value: string | Date | null | undefined,
    locale: string | undefined,
    now: Date = new Date(),
): string {
    if (!value) {
        return '';
    }

    const seconds = (new Date(value).getTime() - now.getTime()) / 1000;
    const format = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
    const unit = RELATIVE_UNITS.find(([, size]) => Math.abs(seconds) >= size);

    if (!unit) {
        return format.format(0, 'second');
    }

    return format.format(Math.round(seconds / unit[1]), unit[0]);
}
