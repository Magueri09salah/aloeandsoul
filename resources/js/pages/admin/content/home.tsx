import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';

import type { ImageData } from '@/types';

import { ContentImage } from '@/components/admin/content-image';
import { ContentLanguageSwitch, hasFrenchErrors, useContentLanguage } from '@/components/admin/content-language';
import { PageHeader, Panel } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/field';
import { Input, Label, Select, Textarea } from '@/components/ui/input';

/** Home page texts that have a French version ("hero_heading" → "hero_heading_fr"). */
const TEXTS = [
    'hero_heading',
    'hero_text',
    'hero_attribution',
    'hero_image_alt',
    'intro_heading',
    'intro_text',
    'featured_heading',
    'ritual_heading',
    'ritual_intro',
    'blog_heading',
] as const;
type TextKey = (typeof TEXTS)[number];
type HomeTexts = Record<TextKey | `${TextKey}_fr`, string>;

interface Step {
    product_id: number;
    note: string;
    note_fr: string;
}

interface Props {
    home: HomeTexts & {
        hero_image: ImageData;
        has_custom_image: boolean;
        ritual_enabled: boolean;
        ritual_steps: (Omit<Step, 'note_fr'> & { note_fr?: string })[];
        newsletter_enabled: boolean;
        blog_enabled: boolean;
    };
    announcement: { enabled: boolean; text: string; link_label: string; link_url: string; text_fr: string; link_label_fr: string };
    products: { id: number; name: string }[];
}

export default function ContentHome({ home, announcement, products }: Props) {
    const texts = Object.fromEntries(TEXTS.flatMap((k) => [[k, home[k] ?? ''], [`${k}_fr`, home[`${k}_fr`] ?? '']])) as HomeTexts;
    const form = useForm({
        announcement: { ...announcement, text_fr: announcement.text_fr ?? '', link_label_fr: announcement.link_label_fr ?? '' },
        home: {
            ...texts,
            ritual_enabled: home.ritual_enabled,
            ritual_steps: home.ritual_steps.map((s) => ({ ...s, note_fr: s.note_fr ?? '' })) as Step[],
            newsletter_enabled: home.newsletter_enabled,
            blog_enabled: home.blog_enabled,
        },
    });
    const c = useContentLanguage();

    const err = (k: string) => (form.errors as Record<string, string | undefined>)[k];
    const setHome = <K extends keyof typeof form.data.home>(key: K, value: (typeof form.data.home)[K]) => form.setData('home', { ...form.data.home, [key]: value });
    const setAnn = <K extends keyof typeof form.data.announcement>(key: K, value: (typeof form.data.announcement)[K]) =>
        form.setData('announcement', { ...form.data.announcement, [key]: value });
    const setStep = (i: number, patch: Partial<Step>) => setHome('ritual_steps', form.data.home.ritual_steps.map((s, j) => (j === i ? { ...s, ...patch } : s)));

    /** A home page text in the language being edited. */
    const text = (key: TextKey) => {
        const name = c.field(key);
        return {
            name: `home.${name}`,
            error: err(`home.${name}`),
            value: form.data.home[name],
            placeholder: c.source(form.data.home[key]),
            onChange: (e: { target: { value: string } }) => setHome(name, e.target.value),
        };
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put('/admin/content/home', { preserveScroll: true });
    };

    const heroHeading = text('hero_heading');
    const heroText = text('hero_text');
    const heroAttribution = text('hero_attribution');
    const heroAlt = text('hero_image_alt');
    const introHeading = text('intro_heading');
    const introText = text('intro_text');
    const featuredHeading = text('featured_heading');
    const ritualHeading = text('ritual_heading');
    const ritualIntro = text('ritual_intro');
    const blogHeading = text('blog_heading');

    return (
        <>
            <PageHeader title="Home page" description="Text and images on the home page, and the announcement bar shown on every page." />
            <div className="space-y-6">
                <Panel title="Hero photograph">
                    <ContentImage slot="hero" image={home.hero_image} isCustom={home.has_custom_image} />
                </Panel>

                <form onSubmit={submit} noValidate className="space-y-6">
                    <ContentLanguageSwitch value={c.lang} onChange={c.setLang} frenchErrors={hasFrenchErrors(form.errors)} />

                    <Panel title="Announcement bar" actions={<Switch aria-label="Show announcement bar" checked={form.data.announcement.enabled} onCheckedChange={(v) => setAnn('enabled', v)} />}>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Field label={c.label('Message')} name={`announcement.${c.field('text')}`} error={err(`announcement.${c.field('text')}`)} className="sm:col-span-3">
                                {(p) => (
                                    <Input
                                        {...p}
                                        value={form.data.announcement[c.field('text')]}
                                        onChange={(e) => setAnn(c.field('text'), e.target.value)}
                                        placeholder={c.source(form.data.announcement.text)}
                                        maxLength={140}
                                    />
                                )}
                            </Field>
                            <Field label={c.label('Link text')} name={`announcement.${c.field('link_label')}`} error={err(`announcement.${c.field('link_label')}`)} optional>
                                {(p) => (
                                    <Input
                                        {...p}
                                        value={form.data.announcement[c.field('link_label')]}
                                        onChange={(e) => setAnn(c.field('link_label'), e.target.value)}
                                        placeholder={c.source(form.data.announcement.link_label)}
                                        maxLength={40}
                                    />
                                )}
                            </Field>
                            <Field
                                label="Link"
                                name="announcement.link_url"
                                error={err('announcement.link_url')}
                                hint="e.g. /shop/beauty. Links to this site open in the visitor’s language."
                                optional
                                className="sm:col-span-2"
                            >
                                {(p) => <Input {...p} value={form.data.announcement.link_url} onChange={(e) => setAnn('link_url', e.target.value)} maxLength={255} />}
                            </Field>
                        </div>
                    </Panel>

                    <Panel title="Hero">
                        <div className="space-y-4">
                            <Field label={c.label('Headline')} name={heroHeading.name} error={heroHeading.error} required={!c.fr} optional={c.fr}>
                                {(p) => <Input {...p} value={heroHeading.value} onChange={heroHeading.onChange} placeholder={heroHeading.placeholder} maxLength={120} />}
                            </Field>
                            <Field label={c.label('Quote')} name={heroText.name} error={heroText.error} hint="In the founder’s own words." optional>
                                {(p) => <Textarea {...p} rows={3} value={heroText.value} onChange={heroText.onChange} placeholder={heroText.placeholder} maxLength={400} />}
                            </Field>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label={c.label('Quote attribution')} name={heroAttribution.name} error={heroAttribution.error} optional>
                                    {(p) => <Input {...p} value={heroAttribution.value} onChange={heroAttribution.onChange} placeholder={heroAttribution.placeholder} maxLength={80} />}
                                </Field>
                                <Field
                                    label={c.label('Photograph description')}
                                    name={heroAlt.name}
                                    error={heroAlt.error}
                                    hint="For screen readers and search engines."
                                    required={!c.fr}
                                    optional={c.fr}
                                >
                                    {(p) => <Input {...p} value={heroAlt.value} onChange={heroAlt.onChange} placeholder={heroAlt.placeholder} maxLength={160} />}
                                </Field>
                            </div>
                        </div>
                    </Panel>

                    <Panel title="Introduction and featured products">
                        <div className="space-y-4">
                            <Field label={c.label('Introduction heading')} name={introHeading.name} error={introHeading.error} optional>
                                {(p) => <Input {...p} value={introHeading.value} onChange={introHeading.onChange} placeholder={introHeading.placeholder} maxLength={120} />}
                            </Field>
                            <Field label={c.label('Introduction text')} name={introText.name} error={introText.error} optional>
                                {(p) => <Textarea {...p} rows={3} value={introText.value} onChange={introText.onChange} placeholder={introText.placeholder} maxLength={600} />}
                            </Field>
                            <Field
                                label={c.label('Featured products heading')}
                                name={featuredHeading.name}
                                error={featuredHeading.error}
                                hint="Choose which products appear with the “Featured” switch on each product."
                                required={!c.fr}
                                optional={c.fr}
                            >
                                {(p) => <Input {...p} value={featuredHeading.value} onChange={featuredHeading.onChange} placeholder={featuredHeading.placeholder} maxLength={80} />}
                            </Field>
                        </div>
                    </Panel>

                    <Panel
                        title="Routine section"
                        description="An optional step-by-step routine made of published products."
                        actions={<Switch aria-label="Show routine section" checked={form.data.home.ritual_enabled} onCheckedChange={(v) => setHome('ritual_enabled', v)} />}
                    >
                        <div className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label={c.label('Heading')} name={ritualHeading.name} error={ritualHeading.error} optional>
                                    {(p) => <Input {...p} value={ritualHeading.value} onChange={ritualHeading.onChange} placeholder={ritualHeading.placeholder} maxLength={80} />}
                                </Field>
                                <Field label={c.label('Introduction')} name={ritualIntro.name} error={ritualIntro.error} optional>
                                    {(p) => <Input {...p} value={ritualIntro.value} onChange={ritualIntro.onChange} placeholder={ritualIntro.placeholder} maxLength={400} />}
                                </Field>
                            </div>
                            <ol className="space-y-2">
                                {form.data.home.ritual_steps.map((step, i) => (
                                    <li key={i} className="flex flex-wrap items-center gap-2">
                                        <span className="w-6 text-sm text-stone">{i + 1}.</span>
                                        <Select
                                            aria-label={`Step ${i + 1} product`}
                                            value={step.product_id}
                                            onChange={(e) => setStep(i, { product_id: Number(e.target.value) })}
                                            disabled={c.fr}
                                            className="min-w-56"
                                        >
                                            {products.map((p) => (
                                                <option key={p.id} value={p.id}>
                                                    {p.name}
                                                </option>
                                            ))}
                                        </Select>
                                        <Input
                                            aria-label={c.label(`Step ${i + 1} note`)}
                                            placeholder={c.fr ? step.note || 'Note courte, ex. Le matin' : 'Short note, e.g. Morning'}
                                            value={c.fr ? step.note_fr : step.note}
                                            onChange={(e) => setStep(i, c.fr ? { note_fr: e.target.value } : { note: e.target.value })}
                                            maxLength={160}
                                            className="flex-1"
                                        />
                                        {!c.fr && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Remove step ${i + 1}`}
                                                onClick={() => setHome('ritual_steps', form.data.home.ritual_steps.filter((_, j) => j !== i))}
                                            >
                                                <Trash2 aria-hidden="true" />
                                            </Button>
                                        )}
                                    </li>
                                ))}
                            </ol>
                            {products.length === 0 ? (
                                <p className="text-sm text-stone">Publish products first to build a routine.</p>
                            ) : (
                                !c.fr &&
                                form.data.home.ritual_steps.length < 5 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setHome('ritual_steps', [...form.data.home.ritual_steps, { product_id: products[0]!.id, note: '', note_fr: '' }])}
                                    >
                                        <Plus aria-hidden="true" /> Add step
                                    </Button>
                                )
                            )}
                        </div>
                    </Panel>

                    <Panel
                        title="Latest blog posts"
                        description="Shows the three newest posts when the blog has published posts."
                        actions={<Switch aria-label="Show latest blog posts" checked={form.data.home.blog_enabled} onCheckedChange={(v) => setHome('blog_enabled', v)} />}
                    >
                        <Field label={c.label('Heading')} name={blogHeading.name} error={blogHeading.error} optional className="max-w-md">
                            {(p) => <Input {...p} value={blogHeading.value} onChange={blogHeading.onChange} placeholder={blogHeading.placeholder} maxLength={80} />}
                        </Field>
                        <p className="mt-3 text-sm text-stone">
                            The collaborators section is managed under{' '}
                            <a href="/admin/collaborators" className="underline underline-offset-4">
                                Collaborators
                            </a>
                            .
                        </p>
                    </Panel>

                    <Panel title="Newsletter">
                        <div className="flex items-center gap-3">
                            <Switch id="newsletter" checked={form.data.home.newsletter_enabled} onCheckedChange={(v) => setHome('newsletter_enabled', v)} />
                            <Label htmlFor="newsletter" className="font-normal">
                                Show the newsletter sign-up in the footer
                            </Label>
                        </div>
                    </Panel>

                    <div className="sticky bottom-0 z-10 flex justify-end border-t border-line bg-panel/95 py-3 backdrop-blur">
                        <Button type="submit" loading={form.processing}>
                            Save home page
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
