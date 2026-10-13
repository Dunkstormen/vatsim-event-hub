export function formatUtc(iso: string, options: Intl.DateTimeFormatOptions) {
    return new Date(iso).toLocaleString('en-GB', { ...options, timeZone: 'UTC' });
}

export function formatTimeRange(startsAt: string, endsAt: string) {
    const start = formatUtc(startsAt, { hour: '2-digit', minute: '2-digit' });
    const end = formatUtc(endsAt, { hour: '2-digit', minute: '2-digit' });

    return `${start}–${end}z`;
}

export function daysUntil(iso: string) {
    const ms = new Date(iso).getTime() - Date.now();

    return Math.max(0, Math.ceil(ms / 86_400_000));
}
