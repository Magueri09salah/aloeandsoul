import { useForm } from '@inertiajs/react';
import { useId, type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useI18n } from '@/lib/i18n';

/** Email opt-in with explicit, unticked consent. */
export function NewsletterForm({ tone = 'dark' }: { tone?: 'dark' | 'light' }) {
    const id = useId();
    const form = useForm({ email: '', consent: false, website: '' });
    const { t, to } = useI18n();

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(to('/newsletter'), { preserveScroll: true, onSuccess: () => form.reset() });
    };

    const dark = tone === 'dark';

    return (
        <form onSubmit={submit} noValidate className="w-full max-w-md">
            <div className="flex gap-2">
                <label htmlFor={`${id}-email`} className="sr-only">
                    {t('Email address')}
                </label>
                <input
                    id={`${id}-email`}
                    type="email"
                    autoComplete="email"
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    placeholder={t('Your email address')}
                    aria-invalid={form.errors.email ? true : undefined}
                    aria-describedby={form.errors.email ? `${id}-error` : undefined}
                    className={
                        dark
                            ? 'h-11 w-full rounded-full border border-ivory/30 bg-transparent px-5 text-ivory placeholder:text-ivory/50 focus-visible:border-ivory focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ivory'
                            : 'h-11 w-full rounded-full border border-line bg-white px-5 placeholder:text-stone/70'
                    }
                />
                {/* Honeypot: hidden from people and assistive technology. */}
                <input
                    type="text"
                    name="website"
                    tabIndex={-1}
                    autoComplete="off"
                    value={form.data.website}
                    onChange={(e) => form.setData('website', e.target.value)}
                    className="hidden"
                    aria-hidden="true"
                />
                <Button type="submit" loading={form.processing} variant={dark ? 'quiet' : 'primary'} className="shrink-0">
                    {t('Subscribe')}
                </Button>
            </div>
            <div className="mt-3 flex items-start gap-3">
                <Checkbox
                    id={`${id}-consent`}
                    checked={form.data.consent}
                    onCheckedChange={(v) => form.setData('consent', v === true)}
                    aria-invalid={form.errors.consent ? true : undefined}
                    className={dark ? 'border-ivory/50 bg-transparent data-[state=checked]:border-ivory data-[state=checked]:bg-ivory data-[state=checked]:text-ink' : undefined}
                />
                <label htmlFor={`${id}-consent`} className={dark ? 'text-sm text-ivory/75' : 'text-sm text-stone'}>
                    {/* Same wording as NewsletterRequest::CONSENT_TEXT, which is stored as the record of consent. */}
                    {t('I agree to receive occasional emails from Aloe & Soul. I can unsubscribe at any time.')}
                </label>
            </div>
            {(form.errors.email || form.errors.consent || form.errors.website) && (
                <p id={`${id}-error`} role="alert" className={dark ? 'mt-2 text-sm text-[#F2B8AC]' : 'mt-2 text-sm text-clay'}>
                    {form.errors.email ?? form.errors.consent ?? form.errors.website}
                </p>
            )}
        </form>
    );
}
