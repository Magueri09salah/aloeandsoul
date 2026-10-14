import * as React from 'react';

import { Label } from '@/components/ui/input';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export interface ControlProps {
    id: string;
    name: string;
    'aria-invalid'?: true;
    'aria-describedby'?: string;
    required?: boolean;
}

interface FieldProps {
    label: React.ReactNode;
    name: string;
    error?: string;
    hint?: React.ReactNode;
    required?: boolean;
    optional?: boolean;
    className?: string;
    children: (control: ControlProps) => React.ReactNode;
}

/**
 * Label + control + hint + error, wired together with ids so screen readers
 * announce the hint and the error with the control.
 */
export function Field({ label, name, error, hint, required, optional, className, children }: FieldProps) {
    const id = `${React.useId()}-${name}`;
    const { t } = useI18n();
    const hintId = hint ? `${id}-hint` : undefined;
    const errorId = error ? `${id}-error` : undefined;

    return (
        <div className={cn('flex flex-col gap-1.5', className)}>
            <Label htmlFor={id}>
                {label}
                {optional && <span className="ml-1 font-normal text-stone">{t('(optional)')}</span>}
            </Label>
            {children({
                id,
                name,
                required,
                'aria-invalid': error ? true : undefined,
                'aria-describedby': [hintId, errorId].filter(Boolean).join(' ') || undefined,
            })}
            {hint && (
                <p id={hintId} className="text-sm text-stone">
                    {hint}
                </p>
            )}
            {error && (
                <p id={errorId} className="text-sm text-clay" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}

/** Summary of errors at the top of a form; focus moves here on failed submit. */
export function FormErrorSummary({ errors, className }: { errors: Record<string, string | undefined>; className?: string }) {
    const messages = Object.values(errors).filter((m): m is string => Boolean(m));
    const ref = React.useRef<HTMLDivElement>(null);
    const { t } = useI18n();

    React.useEffect(() => {
        if (messages.length > 0) ref.current?.focus();
    }, [messages.length]);

    if (messages.length === 0) return null;

    return (
        <div
            ref={ref}
            tabIndex={-1}
            role="alert"
            className={cn('rounded-(--radius-control) border border-clay/40 bg-clay-soft px-4 py-3 text-sm text-clay', className)}
        >
            {messages.length === 1 ? (
                <p>{messages[0]}</p>
            ) : (
                <>
                    <p className="font-medium">{t('Please check the following:')}</p>
                    <ul className="mt-1 list-disc pl-5">
                        {messages.map((m) => (
                            <li key={m}>{m}</li>
                        ))}
                    </ul>
                </>
            )}
        </div>
    );
}
