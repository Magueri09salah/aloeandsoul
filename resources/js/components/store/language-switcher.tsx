import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** Each language named in itself, so a visitor finds theirs whatever the page language. */
export const LANGUAGE_NAMES: Record<string, string> = {
    en: 'English',
    fr: 'Français',
};

/** Tooltip of the switch, written in the language it leads to. */
const SWITCH_TITLES: Record<string, string> = {
    en: 'Read this page in English',
    fr: 'Lire cette page en français',
};

/**
 * Links to this page in the other language. A full page load (not an Inertia
 * visit) so the document language, metadata and translations all switch.
 */
export function LanguageToggle({ className }: { className?: string }) {
    const { alternates } = useShared();
    const { locale } = useI18n();
    const target = Object.keys(LANGUAGE_NAMES).find((code) => code !== locale);
    const href = target ? alternates?.[target] : undefined;
    if (!target || !href) return null;

    return (
        <a
            href={href}
            hrefLang={target}
            lang={target}
            className={cn('inline-flex h-11 min-w-11 shrink-0 items-center justify-center rounded-full px-2 text-[0.95rem] hover:bg-sand sm:px-3', className)}
            title={SWITCH_TITLES[target]}
        >
            {/* "FR" on small phones, the full name from 640px. */}
            <span className="sm:hidden" aria-hidden="true">
                {target.toUpperCase()}
            </span>
            <span className="max-sm:sr-only">{LANGUAGE_NAMES[target]}</span>
        </a>
    );
}

/** Every language, the current one marked; for the footer and the mobile menu. */
export function LanguageList({ className, linkClassName }: { className?: string; linkClassName?: string }) {
    const { alternates } = useShared();
    const { locale, t } = useI18n();

    return (
        <nav aria-label={t('Language')} className={className}>
            <ul className="flex flex-wrap gap-x-5 gap-y-2">
                {Object.entries(LANGUAGE_NAMES).map(([code, name]) => (
                    <li key={code}>
                        {code === locale ? (
                            <span aria-current="true" className={cn(linkClassName, 'font-medium underline underline-offset-4')}>
                                {name}
                            </span>
                        ) : (
                            <a href={alternates?.[code] ?? (code === 'en' ? '/' : `/${code}`)} hrefLang={code} lang={code} className={linkClassName}>
                                {name}
                            </a>
                        )}
                    </li>
                ))}
            </ul>
        </nav>
    );
}
