import { useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { ContentLanguageSwitch, hasFrenchErrors, useContentLanguage } from '@/components/admin/content-language';
import { PageHeader, Panel } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/field';
import { Input, Label, Textarea } from '@/components/ui/input';

interface Props {
    page: {
        id: number;
        title: string;
        slug: string;
        body: string;
        seo_title: string | null;
        seo_description: string | null;
        title_fr: string | null;
        body_fr: string | null;
        seo_title_fr: string | null;
        seo_description_fr: string | null;
        is_published: boolean;
        needs_review: boolean;
        url: string;
    };
}

export default function PageEdit({ page }: Props) {
    const form = useForm({
        title: page.title,
        body: page.body,
        seo_title: page.seo_title ?? '',
        seo_description: page.seo_description ?? '',
        title_fr: page.title_fr ?? '',
        body_fr: page.body_fr ?? '',
        seo_title_fr: page.seo_title_fr ?? '',
        seo_description_fr: page.seo_description_fr ?? '',
        is_published: page.is_published,
        needs_review: page.needs_review,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(`/admin/pages/${page.id}`, { preserveScroll: true });
    };

    const c = useContentLanguage();
    const err = (key: string) => (form.errors as Record<string, string | undefined>)[key];
    const placeholders = (form.data[c.field('body')].match(/\[[^\]]+\]/g) ?? []).length;

    return (
        <>
            <PageHeader
                back={{ href: '/admin/pages', label: 'Policy pages' }}
                title={page.title}
                actions={
                    <Button asChild variant="secondary" size="sm">
                        <a href={page.url} target="_blank" rel="noopener">
                            View page
                        </a>
                    </Button>
                }
            />
            <form onSubmit={submit} noValidate className="space-y-6">
                <ContentLanguageSwitch value={c.lang} onChange={c.setLang} frenchErrors={hasFrenchErrors(form.errors)} />
                <Panel>
                    <div className="space-y-4">
                        <Field label={c.label('Title')} name={c.field('title')} error={err(c.field('title'))} required={!c.fr} optional={c.fr}>
                            {(p) => <Input {...p} value={form.data[c.field('title')]} onChange={(e) => form.setData(c.field('title'), e.target.value)} placeholder={c.source(form.data.title)} maxLength={120} />}
                        </Field>
                        <Field
                            label={c.label('Text')}
                            name={c.field('body')}
                            error={err(c.field('body'))}
                            required={!c.fr}
                            optional={c.fr}
                            hint={
                                <>
                                    Headings start with <code>##</code>, lists with <code>-</code>, bold with <code>**text**</code>.
                                    {placeholders > 0 && <strong className="ml-1 text-dune-deep">{placeholders} placeholder(s) in [brackets] still to complete.</strong>}
                                </>
                            }
                        >
                            {(p) => (
                                <Textarea
                                    {...p}
                                    rows={24}
                                    className="font-mono text-sm"
                                    value={form.data[c.field('body')]}
                                    onChange={(e) => form.setData(c.field('body'), e.target.value)}
                                    placeholder={c.fr ? 'Empty: the French site shows the English text.' : undefined}
                                />
                            )}
                        </Field>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label={c.label('Search title')} name={c.field('seo_title')} error={err(c.field('seo_title'))} optional>
                                {(p) => (
                                    <Input {...p} value={form.data[c.field('seo_title')]} onChange={(e) => form.setData(c.field('seo_title'), e.target.value)} placeholder={c.source(form.data.seo_title)} maxLength={70} />
                                )}
                            </Field>
                            <Field label={c.label('Search description')} name={c.field('seo_description')} error={err(c.field('seo_description'))} optional>
                                {(p) => (
                                    <Input
                                        {...p}
                                        value={form.data[c.field('seo_description')]}
                                        onChange={(e) => form.setData(c.field('seo_description'), e.target.value)}
                                        placeholder={c.source(form.data.seo_description)}
                                        maxLength={170}
                                    />
                                )}
                            </Field>
                        </div>
                        <div className="flex flex-wrap gap-6">
                            <div className="flex items-center gap-2.5">
                                <Checkbox id="published" checked={form.data.is_published} onCheckedChange={(v) => form.setData('is_published', v === true)} />
                                <Label htmlFor="published" className="font-normal">
                                    Published
                                </Label>
                            </div>
                            <div className="flex items-center gap-2.5">
                                <Checkbox id="review" checked={form.data.needs_review} onCheckedChange={(v) => form.setData('needs_review', v === true)} />
                                <Label htmlFor="review" className="font-normal">
                                    Still needs review
                                </Label>
                            </div>
                        </div>
                    </div>
                </Panel>
                <div className="flex justify-end">
                    <Button type="submit" loading={form.processing}>
                        Save page
                    </Button>
                </div>
            </form>
        </>
    );
}
