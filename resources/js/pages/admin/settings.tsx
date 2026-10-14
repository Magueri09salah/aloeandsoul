import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { type FormEvent, type ReactNode } from 'react';

import { ContentLanguageSwitch, hasFrenchErrors, useContentLanguage } from '@/components/admin/content-language';
import { PageHeader, Panel } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/field';
import { Input, Label, Select, Textarea } from '@/components/ui/input';

interface Props {
    settings: {
        store: {
            name: string;
            tagline: string;
            currency: string;
            country: string;
            country_name: string;
            order_prefix: string;
            low_stock_threshold: number;
            max_quantity_per_line: number;
            ordering_enabled: boolean;
            closed_message: string;
            tagline_fr: string;
            closed_message_fr: string;
        };
        checkout: { cod_instructions: string; confirmation_note: string; delivery_info: string; cod_instructions_fr: string; confirmation_note_fr: string; delivery_info_fr: string };
        contact: { email: string; phone: string; whatsapp: string; locations: string[]; hours: string; hours_fr: string };
        social: { instagram: string; facebook: string; tiktok: string; youtube: string };
        seo: { title: string; description: string; title_fr: string; description_fr: string };
    };
    currencies: string[];
    hasOrders: boolean;
}

function SaveBar({ processing, label = 'Save' }: { processing: boolean; label?: string }) {
    return (
        <div className="mt-5 flex justify-end border-t border-line pt-4">
            <Button type="submit" loading={processing}>
                {label}
            </Button>
        </div>
    );
}

function Section({ title, description, onSubmit, children }: { title: string; description?: string; onSubmit: (e: FormEvent) => void; children: ReactNode }) {
    return (
        <Panel title={title} description={description}>
            <form onSubmit={onSubmit} noValidate>
                {children}
            </form>
        </Panel>
    );
}

export default function Settings({ settings, currencies, hasOrders }: Props) {
    const store = useForm(settings.store);
    const checkout = useForm(settings.checkout);
    const contact = useForm(settings.contact);
    const social = useForm(settings.social);
    const seo = useForm(settings.seo);
    // One switch for every customer-facing text on this page.
    const c = useContentLanguage();
    const frenchErrors = [store, checkout, contact, seo].some((f) => hasFrenchErrors(f.errors as Record<string, string | undefined>));

    const save = (form: { put: (url: string, o: object) => void }, group: string) => (e: FormEvent) => {
        e.preventDefault();
        form.put(`/admin/settings/${group}`, { preserveScroll: true });
    };

    return (
        <>
            <PageHeader title="Settings" description="Store-wide settings. Only owners can change these." />
            <ContentLanguageSwitch value={c.lang} onChange={c.setLang} frenchErrors={frenchErrors} className="mb-6" />
            <div className="space-y-6">
                <Section title="Store" onSubmit={save(store, 'store')}>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Store name" name="name" error={store.errors.name} required>
                            {(p) => <Input {...p} value={store.data.name} onChange={(e) => store.setData('name', e.target.value)} />}
                        </Field>
                        <Field label={c.label('Tagline')} name={c.field('tagline')} error={store.errors[c.field('tagline')]} optional>
                            {(p) => <Input {...p} value={store.data[c.field('tagline')]} onChange={(e) => store.setData(c.field('tagline'), e.target.value)} placeholder={c.source(store.data.tagline)} />}
                        </Field>
                        <Field label="Currency" name="currency" error={store.errors.currency} hint={hasOrders ? 'Prices are not converted when you change currency.' : undefined}>
                            {(p) => (
                                <Select {...p} value={store.data.currency} onChange={(e) => store.setData('currency', e.target.value)}>
                                    {currencies.map((c) => (
                                        <option key={c}>{c}</option>
                                    ))}
                                </Select>
                            )}
                        </Field>
                        <div className="grid grid-cols-3 gap-3">
                            <Field label="Country code" name="country" error={store.errors.country} hint="e.g. MA">
                                {(p) => <Input {...p} value={store.data.country} onChange={(e) => store.setData('country', e.target.value.toUpperCase())} maxLength={2} />}
                            </Field>
                            <Field label="Country name" name="country_name" error={store.errors.country_name} className="col-span-2">
                                {(p) => <Input {...p} value={store.data.country_name} onChange={(e) => store.setData('country_name', e.target.value)} />}
                            </Field>
                        </div>
                        <Field label="Order reference prefix" name="order_prefix" error={store.errors.order_prefix} hint="e.g. AS gives AS-2610-K7Q3X">
                            {(p) => <Input {...p} value={store.data.order_prefix} onChange={(e) => store.setData('order_prefix', e.target.value.toUpperCase())} maxLength={6} />}
                        </Field>
                        <div className="grid grid-cols-2 gap-3">
                            <Field label="Low stock alert at" name="low_stock_threshold" error={store.errors.low_stock_threshold}>
                                {(p) => <Input {...p} type="number" min={0} value={store.data.low_stock_threshold} onChange={(e) => store.setData('low_stock_threshold', Number(e.target.value))} />}
                            </Field>
                            <Field label="Max per product" name="max_quantity_per_line" error={store.errors.max_quantity_per_line} hint="Per order line">
                                {(p) => <Input {...p} type="number" min={1} max={99} value={store.data.max_quantity_per_line} onChange={(e) => store.setData('max_quantity_per_line', Number(e.target.value))} />}
                            </Field>
                        </div>
                    </div>
                    <div className="mt-5 rounded-md border border-line p-4">
                        <div className="flex items-center gap-3">
                            <Switch id="ordering" checked={store.data.ordering_enabled} onCheckedChange={(v) => store.setData('ordering_enabled', v)} />
                            <Label htmlFor="ordering">Accept online orders</Label>
                        </div>
                        <Field label={c.label('Message shown when ordering is paused')} name={c.field('closed_message')} error={store.errors[c.field('closed_message')]} className="mt-4">
                            {(p) => (
                                <Textarea
                                    {...p}
                                    rows={2}
                                    value={store.data[c.field('closed_message')]}
                                    onChange={(e) => store.setData(c.field('closed_message'), e.target.value)}
                                    placeholder={c.source(store.data.closed_message)}
                                    maxLength={300}
                                />
                            )}
                        </Field>
                    </div>
                    <SaveBar processing={store.processing} />
                </Section>

                <Section title="Checkout and delivery" description="Shown to customers at checkout and on product pages." onSubmit={save(checkout, 'checkout')}>
                    <div className="space-y-4">
                        {(
                            [
                                ['cod_instructions', 'Cash on delivery instructions', undefined],
                                ['confirmation_note', 'After ordering', 'e.g. We will call you to confirm your order.'],
                                ['delivery_info', 'Delivery information', 'Only state what you can guarantee.'],
                            ] as const
                        ).map(([key, text, hint]) => (
                            <Field
                                key={key}
                                label={c.label(text)}
                                name={c.field(key)}
                                error={checkout.errors[c.field(key)]}
                                hint={hint}
                                required={key === 'cod_instructions' && !c.fr}
                                optional={key !== 'cod_instructions' || c.fr}
                            >
                                {(p) => (
                                    <Textarea
                                        {...p}
                                        rows={2}
                                        value={checkout.data[c.field(key)]}
                                        onChange={(e) => checkout.setData(c.field(key), e.target.value)}
                                        placeholder={c.source(checkout.data[key])}
                                        maxLength={300}
                                    />
                                )}
                            </Field>
                        ))}
                    </div>
                    <SaveBar processing={checkout.processing} />
                </Section>

                <Section title="Contact details" onSubmit={save(contact, 'contact')}>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Field label="Email" name="email" error={contact.errors.email} optional>
                            {(p) => <Input {...p} type="email" value={contact.data.email} onChange={(e) => contact.setData('email', e.target.value)} />}
                        </Field>
                        <Field label="Phone" name="phone" error={contact.errors.phone} optional>
                            {(p) => <Input {...p} type="tel" value={contact.data.phone} onChange={(e) => contact.setData('phone', e.target.value)} />}
                        </Field>
                        <Field label="WhatsApp" name="whatsapp" error={contact.errors.whatsapp} optional>
                            {(p) => <Input {...p} type="tel" value={contact.data.whatsapp} onChange={(e) => contact.setData('whatsapp', e.target.value)} />}
                        </Field>
                        <Field label={c.label('Opening hours')} name={c.field('hours')} error={contact.errors[c.field('hours')]} optional className="sm:col-span-3">
                            {(p) => <Input {...p} value={contact.data[c.field('hours')]} onChange={(e) => contact.setData(c.field('hours'), e.target.value)} placeholder={c.source(contact.data.hours)} maxLength={120} />}
                        </Field>
                    </div>
                    <fieldset className="mt-4">
                        <legend className="text-sm font-medium">Locations</legend>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {contact.data.locations.map((l, i) => (
                                <div key={i} className="flex items-center gap-1">
                                    <Input
                                        aria-label={`Location ${i + 1}`}
                                        value={l}
                                        onChange={(e) => contact.setData('locations', contact.data.locations.map((x, j) => (j === i ? e.target.value : x)))}
                                        className="w-40"
                                    />
                                    <Button type="button" variant="ghost" size="icon-sm" aria-label={`Remove location ${i + 1}`} onClick={() => contact.setData('locations', contact.data.locations.filter((_, j) => j !== i))}>
                                        <Trash2 aria-hidden="true" />
                                    </Button>
                                </div>
                            ))}
                            {contact.data.locations.length < 6 && (
                                <Button type="button" variant="ghost" size="sm" onClick={() => contact.setData('locations', [...contact.data.locations, ''])}>
                                    <Plus aria-hidden="true" /> Add
                                </Button>
                            )}
                        </div>
                    </fieldset>
                    <SaveBar processing={contact.processing} />
                </Section>

                <Section title="Social media" description="Full https:// links. Leave empty to hide." onSubmit={save(social, 'social')}>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {(['instagram', 'facebook', 'tiktok', 'youtube'] as const).map((k) => (
                            <Field key={k} label={k[0]!.toUpperCase() + k.slice(1)} name={k} error={social.errors[k]} optional>
                                {(p) => <Input {...p} type="url" value={social.data[k]} onChange={(e) => social.setData(k, e.target.value)} placeholder="https://" />}
                            </Field>
                        ))}
                    </div>
                    <SaveBar processing={social.processing} />
                </Section>

                <Section title="Search engines" description="Default title and description for the home page and pages without their own." onSubmit={save(seo, 'seo')}>
                    <div className="space-y-4">
                        <Field label={c.label('Title')} name={c.field('title')} error={seo.errors[c.field('title')]} hint={`${seo.data[c.field('title')].length}/70`} required={!c.fr} optional={c.fr}>
                            {(p) => <Input {...p} value={seo.data[c.field('title')]} onChange={(e) => seo.setData(c.field('title'), e.target.value)} placeholder={c.source(seo.data.title)} maxLength={70} />}
                        </Field>
                        <Field
                            label={c.label('Description')}
                            name={c.field('description')}
                            error={seo.errors[c.field('description')]}
                            hint={`${seo.data[c.field('description')].length}/170`}
                            required={!c.fr}
                            optional={c.fr}
                        >
                            {(p) => (
                                <Textarea
                                    {...p}
                                    rows={2}
                                    value={seo.data[c.field('description')]}
                                    onChange={(e) => seo.setData(c.field('description'), e.target.value)}
                                    placeholder={c.source(seo.data.description)}
                                    maxLength={170}
                                />
                            )}
                        </Field>
                    </div>
                    <SaveBar processing={seo.processing} />
                </Section>
            </div>
        </>
    );
}
