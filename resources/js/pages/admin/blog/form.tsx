import { router, useForm } from '@inertiajs/react';
import { ExternalLink, Upload } from 'lucide-react';
import { type FormEvent } from 'react';

import type { ImageData } from '@/types';

import { PageHeader, Panel } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { ConfirmDialog } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input, Label, Textarea } from '@/components/ui/input';
import { useObjectUrl } from '@/hooks/use-object-url';

interface PostData {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    status: 'draft' | 'published';
    status_label: string;
    published_at: string | null;
    cover: ImageData | null;
    cover_alt: string | null;
    seo_title: string | null;
    seo_description: string | null;
    url: string | null;
}

export default function BlogPostForm({ post }: { post: PostData | null }) {
    const form = useForm<{
        title: string;
        slug: string;
        excerpt: string;
        body: string;
        status: 'draft' | 'published';
        published_at: string;
        cover: File | null;
        remove_cover: boolean;
        cover_alt: string;
        seo_title: string;
        seo_description: string;
    }>({
        title: post?.title ?? '',
        slug: post?.slug ?? '',
        excerpt: post?.excerpt ?? '',
        body: post?.body ?? '',
        status: post?.status ?? 'draft',
        published_at: post?.published_at ?? '',
        cover: null,
        remove_cover: false,
        cover_alt: post?.cover_alt ?? '',
        seo_title: post?.seo_title ?? '',
        seo_description: post?.seo_description ?? '',
    });

    const preview = useObjectUrl(form.data.cover);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { forceFormData: true, preserveScroll: true, onSuccess: () => form.setData((d) => ({ ...d, cover: null, remove_cover: false })) };
        if (post) {
            form.transform((d) => ({ ...d, _method: 'put' }));
            form.post(`/admin/blog/${post.id}`, options);
        } else {
            form.post('/admin/blog', options);
        }
    };

    const coverSrc = preview ?? (form.data.remove_cover ? null : (post?.cover?.src ?? null));
    const scheduled = form.data.status === 'published' && form.data.published_at !== '' && new Date(form.data.published_at) > new Date();

    return (
        <>
            <PageHeader
                back={{ href: '/admin/blog', label: 'Blog' }}
                title={post ? post.title : 'New post'}
                description={
                    post && (
                        <span className="flex flex-wrap items-center gap-2">
                            <Badge tone={post.status_label === 'Published' ? 'success' : post.status_label === 'Scheduled' ? 'info' : 'warning'}>{post.status_label}</Badge>
                            {post.url && (
                                <a href={post.url} target="_blank" rel="noopener" className="inline-flex items-center gap-1 text-sm underline underline-offset-4">
                                    View post <ExternalLink className="size-3.5" aria-hidden="true" />
                                </a>
                            )}
                        </span>
                    )
                }
            />

            <form onSubmit={submit} noValidate className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Panel>
                        <div className="space-y-5">
                            <Field label="Title" name="title" error={form.errors.title} required>
                                {(p) => <Input {...p} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} maxLength={160} className="text-lg" />}
                            </Field>
                            <Field label="Summary" name="excerpt" error={form.errors.excerpt} hint="One or two sentences shown on the blog page and in search results." optional>
                                {(p) => <Textarea {...p} rows={2} value={form.data.excerpt} onChange={(e) => form.setData('excerpt', e.target.value)} maxLength={300} />}
                            </Field>
                            <Field
                                label="Text"
                                name="body"
                                error={form.errors.body}
                                required
                                hint={
                                    <>
                                        Leave an empty line between paragraphs. Headings start with <code>##</code>, lists with <code>-</code>, bold is <code>**text**</code>, links are{' '}
                                        <code>[text](https://…)</code>. Share your experience; avoid promising that a product treats or cures anything.
                                    </>
                                }
                            >
                                {(p) => <Textarea {...p} rows={20} className="font-serif text-base leading-relaxed" value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} />}
                            </Field>
                        </div>
                    </Panel>

                    <Panel title="Search engines" description="Optional. By default the title and summary are used.">
                        <div className="space-y-5">
                            <Field label="Web address" name="slug" error={form.errors.slug} hint={`/blog/${form.data.slug || '…'} (leave empty to create it from the title)`} optional>
                                {(p) => <Input {...p} value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} maxLength={180} />}
                            </Field>
                            <Field label="Page title" name="seo_title" error={form.errors.seo_title} hint={`${form.data.seo_title.length}/70`} optional>
                                {(p) => <Input {...p} value={form.data.seo_title} onChange={(e) => form.setData('seo_title', e.target.value)} maxLength={70} />}
                            </Field>
                            <Field label="Description" name="seo_description" error={form.errors.seo_description} hint={`${form.data.seo_description.length}/170`} optional>
                                {(p) => <Textarea {...p} rows={2} value={form.data.seo_description} onChange={(e) => form.setData('seo_description', e.target.value)} maxLength={170} />}
                            </Field>
                        </div>
                    </Panel>
                </div>

                <div className="space-y-6">
                    <Panel title="Publishing">
                        <fieldset className="space-y-3">
                            <legend className="sr-only">Status</legend>
                            {(['draft', 'published'] as const).map((s) => (
                                <label key={s} className="flex cursor-pointer items-start gap-3">
                                    <input
                                        type="radio"
                                        name="status"
                                        value={s}
                                        checked={form.data.status === s}
                                        onChange={() => form.setData('status', s)}
                                        className="mt-1 size-4 accent-ink"
                                    />
                                    <span>
                                        <span className="block font-medium">{s === 'draft' ? 'Draft' : 'Published'}</span>
                                        <span className="text-sm text-stone">{s === 'draft' ? 'Only visible here.' : 'Visible on the blog from the date below.'}</span>
                                    </span>
                                </label>
                            ))}
                        </fieldset>
                        {form.data.status === 'published' && (
                            <Field
                                label="Publication date"
                                name="published_at"
                                error={form.errors.published_at}
                                hint={scheduled ? 'In the future: the post will appear automatically on this date.' : 'Leave empty to publish now.'}
                                className="mt-4"
                                optional
                            >
                                {(p) => <Input {...p} type="datetime-local" value={form.data.published_at} onChange={(e) => form.setData('published_at', e.target.value)} />}
                            </Field>
                        )}
                        <Button type="submit" className="mt-5 w-full" loading={form.processing}>
                            {post ? 'Save post' : form.data.status === 'published' ? (scheduled ? 'Schedule post' : 'Publish post') : 'Save draft'}
                        </Button>
                    </Panel>

                    <Panel title="Cover photo" description="Landscape photos work best, at least 800 px wide.">
                        {coverSrc ? (
                            <img src={coverSrc} alt="" className="aspect-[4/3] w-full rounded object-cover" />
                        ) : (
                            <div className="flex aspect-[4/3] w-full items-center justify-center rounded bg-sand text-sm text-stone">No cover photo</div>
                        )}
                        <label className="mt-3 flex cursor-pointer items-center justify-center gap-2 rounded-md border border-dashed border-stone/40 px-4 py-3 text-sm hover:border-ink">
                            <Upload className="size-4 text-stone" aria-hidden="true" />
                            {form.data.cover ? form.data.cover.name : 'Choose a photo'}
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                className="sr-only"
                                onChange={(e) => {
                                    form.setData('cover', e.target.files?.[0] ?? null);
                                    form.setData('remove_cover', false);
                                }}
                            />
                        </label>
                        {form.errors.cover && (
                            <p className="mt-2 text-sm text-clay" role="alert">
                                {form.errors.cover}
                            </p>
                        )}
                        {post?.cover && !form.data.cover && (
                            <div className="mt-3 flex items-center gap-2.5">
                                <Checkbox id="remove-cover" checked={form.data.remove_cover} onCheckedChange={(v) => form.setData('remove_cover', v === true)} />
                                <Label htmlFor="remove-cover" className="font-normal">
                                    Remove the cover photo
                                </Label>
                            </div>
                        )}
                        <Field label="Photo description" name="cover_alt" error={form.errors.cover_alt} hint="For screen readers and search engines." optional className="mt-4">
                            {(p) => <Input {...p} value={form.data.cover_alt} onChange={(e) => form.setData('cover_alt', e.target.value)} maxLength={160} />}
                        </Field>
                    </Panel>

                    {post && (
                        <Panel title="Delete">
                            <ConfirmDialog
                                trigger={
                                    <Button type="button" variant="destructive-outline" size="sm">
                                        Delete post
                                    </Button>
                                }
                                title="Delete this post?"
                                description={`“${post.title}” and its cover photo will be removed permanently.`}
                                confirmLabel="Delete post"
                                onConfirm={() => router.delete(`/admin/blog/${post.id}`)}
                            />
                        </Panel>
                    )}
                </div>
            </form>
        </>
    );
}
