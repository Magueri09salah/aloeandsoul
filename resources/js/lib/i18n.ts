/**
 * Storefront translations. Interface text is written in English in the code
 * and looked up in lang/fr.json (shared with Laravel's __()), so the English
 * sentence is the key. Missing translations fall back to the English text.
 *
 * Placeholders use Laravel's ":name" syntax. Plural strings hold two forms,
 * "One item|:count items"; French treats 0 and 1 as singular.
 */
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';

import fr from '../../../lang/fr.json';
import { formatDate } from '@/lib/date';
import { formatMoney } from '@/lib/money';
import type { SharedProps } from '@/types';

export const DEFAULT_LOCALE = 'en';

const DICTIONARIES: Record<string, Record<string, string>> = { fr };

type Replacements = Record<string, string | number>;

function interpolate(text: string, replace?: Replacements): string {
    if (!replace) return text;
    // Longest names first so ":count" never eats part of ":counter".
    return Object.keys(replace)
        .sort((a, b) => b.length - a.length)
        .reduce((out, name) => out.replaceAll(`:${name}`, String(replace[name])), text);
}

export function translate(locale: string, key: string, replace?: Replacements): string {
    return interpolate(DICTIONARIES[locale]?.[key] ?? key, replace);
}

export function translateChoice(locale: string, key: string, count: number, replace?: Replacements): string {
    const forms = (DICTIONARIES[locale]?.[key] ?? key).split('|');
    const singular = locale === 'fr' ? Math.abs(count) < 2 : count === 1;
    const form = (singular ? forms[0] : forms[1]) ?? forms[0] ?? key;
    return interpolate(form, { count, ...replace });
}

/** A storefront path in another language: ("/shop", "fr") → "/fr/shop". Leaves external links alone. */
export function localizePath(path: string, locale: string): string {
    if (locale === DEFAULT_LOCALE || !path.startsWith('/') || path.startsWith('//')) return path;
    if (new RegExp(`^/${locale}(?=[/?#]|$)`).test(path)) return path;
    if (path === '/' || path.startsWith('/?') || path.startsWith('/#')) return `/${locale}${path.slice(1)}`;
    return `/${locale}${path}`;
}

export interface I18n {
    locale: string;
    /** Translate an English sentence. */
    t: (key: string, replace?: Replacements) => string;
    /** Translate a "singular|plural" sentence for a count (also available as :count). */
    tc: (key: string, count: number, replace?: Replacements) => string;
    /** Link to a storefront path in the current language. */
    to: (path: string) => string;
    /** Amount in minor units, formatted for the current language. */
    money: (minor: number, currency?: string) => string;
    /** ISO date, formatted for the current language in the store's time zone. */
    date: (iso: string | null | undefined) => string;
}

export function useI18n(): I18n {
    const { store } = usePage<SharedProps>().props;
    const { locale, currency, timezone } = store;

    return useMemo(
        () => ({
            locale,
            t: (key, replace) => translate(locale, key, replace),
            tc: (key, count, replace) => translateChoice(locale, key, count, replace),
            to: (path) => localizePath(path, locale),
            money: (minor, code) => formatMoney(minor, code ?? currency, locale),
            date: (iso) => formatDate(iso, timezone, locale),
        }),
        [locale, currency, timezone],
    );
}
