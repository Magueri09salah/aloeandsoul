const DEFAULT_TZ = 'Africa/Casablanca';

export function formatDate(iso: string | null | undefined, timeZone = DEFAULT_TZ): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone }).format(
        new Date(iso),
    );
}

export function formatDateTime(iso: string | null | undefined, timeZone = DEFAULT_TZ): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone,
    }).format(new Date(iso));
}
