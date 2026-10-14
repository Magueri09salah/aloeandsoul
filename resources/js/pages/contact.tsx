import { useForm } from '@inertiajs/react';
import { Mail, MapPin, MessageCircle, Phone } from 'lucide-react';
import { type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input, Textarea } from '@/components/ui/input';
import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';

export default function Contact() {
    const { contact } = useShared();
    const { t, to } = useI18n();
    const form = useForm({ name: '', email: '', phone: '', message: '', website: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(to('/contact'), { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <div className="container-page grid gap-14 py-12 lg:grid-cols-12 lg:py-20">
            <div className="lg:col-span-5">
                <h1 className="font-display text-display-sm">{t('Contact')}</h1>
                <p className="mt-4 text-lg text-stone">{t('A question about a product or an order? Send a message, or reach us directly on WhatsApp for a quick reply.')}</p>

                <ul className="mt-10 space-y-5">
                    {contact?.whatsapp && (
                        <li>
                            <a href={`https://wa.me/${contact.whatsapp}`} target="_blank" rel="noopener noreferrer" className="group flex items-start gap-4">
                                <MessageCircle className="mt-0.5 size-5 text-dune" strokeWidth={1.5} aria-hidden="true" />
                                <span>
                                    <span className="block font-medium group-hover:text-aloe">WhatsApp</span>
                                    <span className="text-stone">{contact.phone_display}</span>
                                </span>
                            </a>
                        </li>
                    )}
                    {contact?.email && (
                        <li>
                            <a href={`mailto:${contact.email}`} className="group flex items-start gap-4">
                                <Mail className="mt-0.5 size-5 text-dune" strokeWidth={1.5} aria-hidden="true" />
                                <span>
                                    <span className="block font-medium group-hover:text-aloe">{t('Email')}</span>
                                    <span className="text-stone">{contact.email}</span>
                                </span>
                            </a>
                        </li>
                    )}
                    {contact?.phone && (
                        <li>
                            <a href={`tel:${contact.phone}`} className="group flex items-start gap-4">
                                <Phone className="mt-0.5 size-5 text-dune" strokeWidth={1.5} aria-hidden="true" />
                                <span>
                                    <span className="block font-medium group-hover:text-aloe">{t('Phone')}</span>
                                    <span className="text-stone">{contact.phone_display}</span>
                                    {contact.hours && <span className="block text-sm text-stone">{contact.hours}</span>}
                                </span>
                            </a>
                        </li>
                    )}
                    {contact?.locations && contact.locations.length > 0 && (
                        <li className="flex items-start gap-4">
                            <MapPin className="mt-0.5 size-5 text-dune" strokeWidth={1.5} aria-hidden="true" />
                            <span>
                                <span className="block font-medium">{t('Where we are')}</span>
                                <span className="text-stone">{contact.locations.join(', ')}</span>
                            </span>
                        </li>
                    )}
                </ul>
            </div>

            <section aria-labelledby="form-heading" className="rounded-lg border border-line bg-white p-6 sm:p-8 lg:col-span-6 lg:col-start-7">
                <h2 id="form-heading" className="font-display text-2xl">
                    {t('Send a message')}
                </h2>
                <form onSubmit={submit} noValidate className="mt-6 space-y-5">
                    <Field label={t('Name')} name="name" error={form.errors.name} required>
                        {(p) => <Input {...p} autoComplete="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} maxLength={120} />}
                    </Field>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field label={t('Email')} name="email" error={form.errors.email} required>
                            {(p) => <Input {...p} type="email" autoComplete="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} maxLength={255} />}
                        </Field>
                        <Field label={t('Phone')} name="phone" optional error={form.errors.phone}>
                            {(p) => <Input {...p} type="tel" autoComplete="tel" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} maxLength={30} />}
                        </Field>
                    </div>
                    <Field label={t('Message')} name="message" error={form.errors.message} required>
                        {(p) => <Textarea {...p} rows={6} value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} maxLength={2000} />}
                    </Field>
                    <input
                        type="text"
                        name="website"
                        tabIndex={-1}
                        autoComplete="off"
                        className="hidden"
                        aria-hidden="true"
                        value={form.data.website}
                        onChange={(e) => form.setData('website', e.target.value)}
                    />
                    <p className="text-sm text-stone">
                        {t('We use your details only to reply to you. See our')}{' '}
                        <a href={to('/privacy-policy')} className="underline underline-offset-4">
                            {t('privacy policy')}
                        </a>
                        .
                    </p>
                    <Button type="submit" loading={form.processing}>
                        {t('Send message')}
                    </Button>
                </form>
            </section>
        </div>
    );
}
