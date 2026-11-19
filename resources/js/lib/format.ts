/**
 * Date and money formatting for the ads/survey screens. Money arrives from the server as ready-made dollar
 * strings (computed from integer micros), so nothing here does arithmetic on amounts.
 */

/** "Nov 10, 2026, 11:50 PM" in the given IANA timezone (the viewer's own when omitted). */
export function formatDateTime(
    iso: string | null | undefined,
    timeZone?: string,
): string {
    if (!iso) {
        return '—';
    }

    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone,
    }).format(date);
}

/** "Nov 10, 2026" for a plain Y-m-d day, with no timezone shifting. */
export function formatDay(day: string | null | undefined): string {
    if (!day) {
        return '—';
    }

    const [year, month, date] = day.slice(0, 10).split('-').map(Number);

    if (!year || !month || !date) {
        return day;
    }

    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(year, month - 1, date)));
}

/** "$1.32" from a server-formatted decimal string such as "1.32". Null renders as an em dash. */
export function usd(amount: string | null | undefined): string {
    if (amount === null || amount === undefined) {
        return '—';
    }

    const negative = amount.startsWith('-');
    const [whole, fraction = '00'] = amount.replace('-', '').split('.');
    const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return `${negative ? '-' : ''}$${grouped}.${fraction}`;
}
