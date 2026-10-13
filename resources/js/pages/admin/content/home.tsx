import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';

import type { ImageData } from '@/types';

import { ContentImage } from '@/components/admin/content-image';
import { PageHeader, Panel } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/field';
import { Input, Label, Select, Textarea } from '@/components/ui/input';

interface Props {
    home: {
        hero_heading: string;
        hero_text: string;
        hero_attribution: string;
        hero_image: ImageData;
        hero_image_alt: string;
        has_custom_image: boolean;
        intro_heading: string;
        intro_text: string;
        featured_heading: string;
        ritual_enabled: boolean;
        ritual_heading: string;
        ritual_intro: string;
        ritual_steps: { product_id: number; note: string }[];
        newsletter_enabled: boolean;
    };
    announcement: { enabled: boolean; text: string; link_label: string; link_url: string };
    products: { id: number; name: string }[];
}

export default function ContentHome({ home, announcement, products }: Props) {
    const form = useForm({
        announcement: { ...announcement },
        home: {
            hero_heading: home.hero_heading,
            hero_text: home.hero_text,
            hero_attribution: home.hero_attribution,
            hero_image_alt: home.hero_image_alt,
            intro_heading: home.intro_heading,
            intro_text: home.intro_text,
            featured_heading: home.featured_heading,
            ritual_enabled: home.ritual_enabled,
            ritual_heading: home.ritual_heading,
            ritual_intro: home.ritual_intro,
            ritual_steps: home.ritual_steps,
            newsletter_enabled: home.newsletter_enabled,
        },
    });

    const err = (k: string) => (form.errors as Record<string, string | undefined>)[k];
    const setHome = <K extends keyof typeof form.data.home>(key: K, value: (typeof form.data.home)[K]) => form.setData('home', { ...form.data.home, [key]: value });
    const setAnn = <K extends keyof typeof form.data.announcement>(key: K, value: (typeof form.data.announcement)[K]) =>
        form.setData('announcement', { ...form.data.announcement, [key]: value });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put('/admin/content/home', { preserveScroll: true });
    };

    return (
        <>
            <PageHeader title="Home page" description="Text and images on the home page, and the announcement bar shown on every page." />
            <div className="space-y-6">
                <Panel title="Hero photograph">
                    <ContentImage slot="hero" image={home.hero_image} isCustom={home.has_custom_image} />
                </Panel>

                <form onSubmit={submit} noValidate className="space-y-6">
                    <Panel title="Announcement bar" actions={<Switch aria-label="Show announcement bar" checked={form.data.announcement.enabled} onCheckedChange={(v) => setAnn('enabled', v)} />}>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Field label="Message" name="announcement.text" error={err('announcement.text')} className="sm:col-span-3">
                                {(p) => <Input {...p} value={form.data.announcement.text} onChange={(e) => setAnn('text', e.target.value)} maxLength={140} />}
                            </Field>
                            <Field label="Link text" name="announcement.link_label" error={err('announcement.link_label')} optional>
                                {(p) => <Input {...p} value={form.data.announcement.link_label} onChange={(e) => setAnn('link_label', e.target.value)} maxLength={40} />}
                            </Field>
                            <Field label="Link" name="announcement.link_url" error={err('announcement.link_url')} hint="e.g. /shop/beauty" optional className="sm:col-span-2">
                                {(p) => <Input {...p} value={form.data.announcement.link_url} onChange={(e) => setAnn('link_url', e.target.value)} maxLength={255} />}
                            </Field>
                        </div>
                    </Panel>

                    <Panel title="Hero">
                        <div className="space-y-4">
                            <Field label="Headline" name="home.hero_heading" error={err('home.hero_heading')} required>
                                {(p) => <Input {...p} value={form.data.home.hero_heading} onChange={(e) => setHome('hero_heading', e.target.value)} maxLength={120} />}
                            </Field>
                            <Field label="Quote" name="home.hero_text" error={err('home.hero_text')} hint="In the founder’s own words." optional>
                                {(p) => <Textarea {...p} rows={3} value={form.data.home.hero_text} onChange={(e) => setHome('hero_text', e.target.value)} maxLength={400} />}
                            </Field>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Quote attribution" name="home.hero_attribution" error={err('home.hero_attribution')} optional>
                                    {(p) => <Input {...p} value={form.data.home.hero_attribution} onChange={(e) => setHome('hero_attribution', e.target.value)} maxLength={80} />}
                                </Field>
                                <Field label="Photograph description" name="home.hero_image_alt" error={err('home.hero_image_alt')} hint="For screen readers and search engines." required>
                                    {(p) => <Input {...p} value={form.data.home.hero_image_alt} onChange={(e) => setHome('hero_image_alt', e.target.value)} maxLength={160} />}
                                </Field>
                            </div>
                        </div>
                    </Panel>

                    <Panel title="Introduction and featured products">
                        <div className="space-y-4">
                            <Field label="Introduction heading" name="home.intro_heading" error={err('home.intro_heading')} optional>
                                {(p) => <Input {...p} value={form.data.home.intro_heading} onChange={(e) => setHome('intro_heading', e.target.value)} maxLength={120} />}
                            </Field>
                            <Field label="Introduction text" name="home.intro_text" error={err('home.intro_text')} optional>
                                {(p) => <Textarea {...p} rows={3} value={form.data.home.intro_text} onChange={(e) => setHome('intro_text', e.target.value)} maxLength={600} />}
                            </Field>
                            <Field label="Featured products heading" name="home.featured_heading" error={err('home.featured_heading')} hint="Choose which products appear with the “Featured” switch on each product." required>
                                {(p) => <Input {...p} value={form.data.home.featured_heading} onChange={(e) => setHome('featured_heading', e.target.value)} maxLength={80} />}
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
                                <Field label="Heading" name="home.ritual_heading" error={err('home.ritual_heading')} optional>
                                    {(p) => <Input {...p} value={form.data.home.ritual_heading} onChange={(e) => setHome('ritual_heading', e.target.value)} maxLength={80} />}
                                </Field>
                                <Field label="Introduction" name="home.ritual_intro" error={err('home.ritual_intro')} optional>
                                    {(p) => <Input {...p} value={form.data.home.ritual_intro} onChange={(e) => setHome('ritual_intro', e.target.value)} maxLength={400} />}
                                </Field>
                            </div>
                            <ol className="space-y-2">
                                {form.data.home.ritual_steps.map((step, i) => (
                                    <li key={i} className="flex flex-wrap items-center gap-2">
                                        <span className="w-6 text-sm text-stone">{i + 1}.</span>
                                        <Select
                                            aria-label={`Step ${i + 1} product`}
                                            value={step.product_id}
                                            onChange={(e) =>
                                                setHome(
                                                    'ritual_steps',
                                                    form.data.home.ritual_steps.map((s, j) => (j === i ? { ...s, product_id: Number(e.target.value) } : s)),
                                                )
                                            }
                                            className="min-w-56"
                                        >
                                            {products.map((p) => (
                                                <option key={p.id} value={p.id}>
                                                    {p.name}
                                                </option>
                                            ))}
                                        </Select>
                                        <Input
                                            aria-label={`Step ${i + 1} note`}
                                            placeholder="Short note, e.g. Morning"
                                            value={step.note}
                                            onChange={(e) => setHome('ritual_steps', form.data.home.ritual_steps.map((s, j) => (j === i ? { ...s, note: e.target.value } : s)))}
                                            maxLength={160}
                                            className="flex-1"
                                        />
                                        <Button type="button" variant="ghost" size="icon" aria-label={`Remove step ${i + 1}`} onClick={() => setHome('ritual_steps', form.data.home.ritual_steps.filter((_, j) => j !== i))}>
                                            <Trash2 aria-hidden="true" />
                                        </Button>
                                    </li>
                                ))}
                            </ol>
                            {products.length === 0 ? (
                                <p className="text-sm text-stone">Publish products first to build a routine.</p>
                            ) : (
                                form.data.home.ritual_steps.length < 5 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setHome('ritual_steps', [...form.data.home.ritual_steps, { product_id: products[0]!.id, note: '' }])}
                                    >
                                        <Plus aria-hidden="true" /> Add step
                                    </Button>
                                )
                            )}
                        </div>
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
