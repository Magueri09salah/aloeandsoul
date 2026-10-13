import { useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

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
        is_published: page.is_published,
        needs_review: page.needs_review,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(`/admin/pages/${page.id}`, { preserveScroll: true });
    };

    const placeholders = (form.data.body.match(/\[[^\]]+\]/g) ?? []).length;

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
                <Panel>
                    <div className="space-y-4">
                        <Field label="Title" name="title" error={form.errors.title} required>
                            {(p) => <Input {...p} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} maxLength={120} />}
                        </Field>
                        <Field
                            label="Text"
                            name="body"
                            error={form.errors.body}
                            required
                            hint={
                                <>
                                    Headings start with <code>##</code>, lists with <code>-</code>, bold with <code>**text**</code>.
                                    {placeholders > 0 && <strong className="ml-1 text-dune-deep">{placeholders} placeholder(s) in [brackets] still to complete.</strong>}
                                </>
                            }
                        >
                            {(p) => <Textarea {...p} rows={24} className="font-mono text-sm" value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} />}
                        </Field>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="Search title" name="seo_title" error={form.errors.seo_title} optional>
                                {(p) => <Input {...p} value={form.data.seo_title} onChange={(e) => form.setData('seo_title', e.target.value)} maxLength={70} />}
                            </Field>
                            <Field label="Search description" name="seo_description" error={form.errors.seo_description} optional>
                                {(p) => <Input {...p} value={form.data.seo_description} onChange={(e) => form.setData('seo_description', e.target.value)} maxLength={170} />}
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
