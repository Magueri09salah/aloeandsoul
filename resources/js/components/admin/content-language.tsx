import { useState } from 'react';

import { cn } from '@/lib/utils';

export type ContentLanguage = 'en' | 'fr';

/**
 * Which language's texts a form is editing. English texts are stored in
 * "name", French ones in "name_fr"; the form shows one set at a time in the
 * same place, so a translation sits exactly where its English source was.
 */
export function useContentLanguage() {
    const [lang, setLang] = useState<ContentLanguage>('en');
    const fr = lang === 'fr';

    return {
        lang,
        setLang,
        fr,
        /** The form key for a text in the language being edited: field('name') → "name" or "name_fr". */
        field: <K extends string>(name: K) => (fr ? `${name}_fr` : name) as K | `${K}_fr`,
        /** Field label, marked when editing French. */
        label: (text: string) => (fr ? `${text} (French)` : text),
        /** The English text as a placeholder for an empty French field. */
        source: (english: string | null | undefined) => (fr && english ? english : undefined),
    };
}

/** Whether any validation error belongs to a French field (shown as a dot on the switch). */
export function hasFrenchErrors(errors: Record<string, string | undefined>): boolean {
    return Object.entries(errors).some(([key, message]) => Boolean(message) && /_fr(\.|$)/.test(key));
}

export function ContentLanguageSwitch({
    value,
    onChange,
    frenchErrors = false,
    className,
}: {
    value: ContentLanguage;
    onChange: (lang: ContentLanguage) => void;
    frenchErrors?: boolean;
    className?: string;
}) {
    return (
        <div className={cn('flex flex-wrap items-center gap-x-4 gap-y-2', className)}>
            <div role="group" aria-label="Language of the texts" className="inline-flex shrink-0 rounded-full border border-line bg-white p-1">
                {(['en', 'fr'] as const).map((code) => (
                    <button
                        key={code}
                        type="button"
                        aria-pressed={value === code}
                        onClick={() => onChange(code)}
                        className={cn(
                            'relative inline-flex h-8 items-center gap-1.5 rounded-full px-4 text-sm transition-colors',
                            value === code ? 'bg-ink text-ivory' : 'text-ink hover:bg-sand',
                        )}
                    >
                        {code === 'en' ? 'English' : 'Français'}
                        {code === 'fr' && frenchErrors && (
                            <>
                                <span className="size-2 rounded-full bg-clay" aria-hidden="true" />
                                <span className="sr-only"> (has errors)</span>
                            </>
                        )}
                    </button>
                ))}
            </div>
            <p className="text-sm text-stone">
                {value === 'fr'
                    ? 'French texts, shown on the French site (/fr). An empty field shows the English text there.'
                    : 'English texts. Switch to Français to write the French version.'}
            </p>
        </div>
    );
}
