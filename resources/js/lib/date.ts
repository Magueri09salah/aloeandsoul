const DEFAULT_TZ = 'Africa/Casablanca';

/** Intl locale for a site language (British English, French). */
function intlLocale(locale: string): string {
    return locale === 'fr' ? 'fr-FR' : 'en-GB';
}

export function formatDate(iso: string | null | undefined, timeZone = DEFAULT_TZ, locale = 'en'): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat(intlLocale(locale), { day: 'numeric', month: 'short', year: 'numeric', timeZone }).format(
        new Date(iso),
    );
}

export function formatDateTime(iso: string | null | undefined, timeZone = DEFAULT_TZ, locale = 'en'): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat(intlLocale(locale), {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone,
    }).format(new Date(iso));
}
