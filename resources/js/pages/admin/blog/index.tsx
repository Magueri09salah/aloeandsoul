import { Link, router, useForm } from '@inertiajs/react';
import { ExternalLink, Plus } from 'lucide-react';
import { type FormEvent } from 'react';

import type { ImageData, Paginated } from '@/types';

import { ContentLanguageSwitch, hasFrenchErrors, useContentLanguage } from '@/components/admin/content-language';
import { EmptyState, PageHeader, Pagination, Panel, Table, td, th } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input, Textarea } from '@/components/ui/input';
import { useShared } from '@/hooks/use-shared';
import { formatDateTime } from '@/lib/date';
import { cn } from '@/lib/utils';

interface Row {
    id: number;
    title: string;
    slug: string;
    status: 'draft' | 'published' | 'scheduled';
    status_label: string;
    published_at: string | null;
    updated_at: string;
    cover: ImageData | null;
    url: string | null;
}

interface Props {
    posts: Paginated<Row>;
    filter: string | null;
    settings: { heading: string; intro: string; heading_fr: string; intro_fr: string };
}

const TONE = { draft: 'warning', published: 'success', scheduled: 'info' } as const;

export default function BlogIndex({ posts, filter, settings }: Props) {
    const { store } = useShared();

    return (
        <>
            <PageHeader
                title="Blog"
                description="Write posts for the blog page. Drafts stay private; a publication date in the future schedules the post."
                actions={
                    <>
                        <Button asChild variant="secondary">
                            <a href="/blog" target="_blank" rel="noopener">
                                View blog <ExternalLink aria-hidden="true" />
                            </a>
                        </Button>
                        <Button asChild>
                            <Link href="/admin/blog/create">
                                <Plus aria-hidden="true" /> New post
                            </Link>
                        </Button>
                    </>
                }
            />

            <div className="mb-4 flex flex-wrap gap-2" role="group" aria-label="Filter posts">
                {[
                    { value: null, label: 'All' },
                    { value: 'published', label: 'Published' },
                    { value: 'scheduled', label: 'Scheduled' },
                    { value: 'draft', label: 'Drafts' },
                ].map((f) => (
                    <button
                        key={f.label}
                        type="button"
                        aria-pressed={filter === f.value}
                        onClick={() => router.get('/admin/blog', f.value ? { status: f.value } : {}, { preserveState: true, replace: true })}
                        className={cn('h-9 rounded-full border px-3.5 text-sm', filter === f.value ? 'border-ink bg-ink text-ivory' : 'border-line bg-white hover:border-stone')}
                    >
                        {f.label}
                    </button>
                ))}
            </div>

            <Panel padded={false}>
                {posts.data.length === 0 ? (
                    <EmptyState title="No posts yet.">
                        <Link href="/admin/blog/create" className="underline underline-offset-4">
                            Write the first post
                        </Link>
                    </EmptyState>
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>Post</th>
                                <th className={th}>Status</th>
                                <th className={th}>Publication date</th>
                                <th className={th}>
                                    <span className="sr-only">Links</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {posts.data.map((p) => (
                                <tr key={p.id} className="hover:bg-panel/50">
                                    <td className={td}>
                                        <div className="flex items-center gap-3">
                                            <div className="h-11 w-16 shrink-0 overflow-hidden rounded bg-sand">
                                                {p.cover && <img src={p.cover.src} alt="" className="h-full w-full object-cover" loading="lazy" />}
                                            </div>
                                            <Link href={`/admin/blog/${p.id}/edit`} className="font-medium hover:underline">
                                                {p.title}
                                            </Link>
                                        </div>
                                    </td>
                                    <td className={td}>
                                        <Badge tone={TONE[p.status]}>{p.status_label}</Badge>
                                    </td>
                                    <td className={cn(td, 'whitespace-nowrap text-stone')}>{p.published_at ? formatDateTime(p.published_at, store.timezone) : '—'}</td>
                                    <td className={cn(td, 'text-right')}>
                                        {p.url && (
                                            <a href={p.url} target="_blank" rel="noopener" className="text-sm underline underline-offset-4">
                                                View
                                            </a>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                )}
                <Pagination page={posts} />
            </Panel>

            <BlogPageText settings={settings} />
        </>
    );
}

function BlogPageText({ settings }: { settings: Props['settings'] }) {
    const form = useForm({ heading: settings.heading, intro: settings.intro, heading_fr: settings.heading_fr, intro_fr: settings.intro_fr });
    const c = useContentLanguage();
    const err = (key: string) => (form.errors as Record<string, string | undefined>)[key];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put('/admin/blog/settings', { preserveScroll: true });
    };

    return (
        <Panel title="Blog page text" description="The title and introduction at the top of the blog page." className="mt-6">
            <form onSubmit={submit} noValidate className="grid gap-4 sm:grid-cols-3">
                <ContentLanguageSwitch value={c.lang} onChange={c.setLang} frenchErrors={hasFrenchErrors(form.errors)} className="sm:col-span-3" />
                <Field label={c.label('Title')} name={c.field('heading')} error={err(c.field('heading'))} required={!c.fr} optional={c.fr}>
                    {(p) => <Input {...p} value={form.data[c.field('heading')]} onChange={(e) => form.setData(c.field('heading'), e.target.value)} placeholder={c.source(form.data.heading)} maxLength={60} />}
                </Field>
                <Field label={c.label('Introduction')} name={c.field('intro')} error={err(c.field('intro'))} optional className="sm:col-span-2">
                    {(p) => (
                        <Textarea {...p} rows={2} value={form.data[c.field('intro')]} onChange={(e) => form.setData(c.field('intro'), e.target.value)} placeholder={c.source(form.data.intro)} maxLength={300} />
                    )}
                </Field>
                <div className="sm:col-span-3">
                    <Button type="submit" variant="secondary" loading={form.processing} disabled={!form.isDirty}>
                        Save text
                    </Button>
                </div>
            </form>
        </Panel>
    );
}
