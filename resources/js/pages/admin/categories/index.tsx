import { router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { Motif } from '@/components/brand/motif';
import { ContentLanguageSwitch, hasFrenchErrors, useContentLanguage } from '@/components/admin/content-language';
import { PageHeader, Panel } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/checkbox';
import { ConfirmDialog, Dialog, DialogContent } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input, Label, Select, Textarea } from '@/components/ui/input';

interface Category {
    id: number;
    name: string;
    slug: string;
    tagline: string | null;
    description: string | null;
    tint: string;
    motif: string;
    sort_order: number;
    is_active: boolean;
    seo_title: string | null;
    seo_description: string | null;
    name_fr: string | null;
    tagline_fr: string | null;
    description_fr: string | null;
    seo_title_fr: string | null;
    seo_description_fr: string | null;
    products_count: number;
    published_products_count: number;
    url: string;
}

export default function Categories({ categories, motifs }: { categories: Category[]; motifs: string[] }) {
    const [editing, setEditing] = useState<Category | 'new' | null>(null);

    return (
        <>
            <PageHeader
                title="Categories"
                description="Each category has its own page in the shop, with its colour and motif."
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus aria-hidden="true" /> Add category
                    </Button>
                }
            />
            <div className="grid gap-4 sm:grid-cols-2">
                {categories.map((c) => (
                    <Panel key={c.id}>
                        <div className="flex gap-4">
                            <div className="flex size-16 shrink-0 items-center justify-center rounded-full p-3 text-dune" style={{ backgroundColor: c.tint }}>
                                <Motif name={c.motif} />
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="font-medium">{c.name}</h2>
                                    {!c.is_active && <Badge tone="warning">Hidden</Badge>}
                                </div>
                                <p className="text-sm text-stone">/shop/{c.slug}</p>
                                <p className="mt-1 text-sm">
                                    {c.published_products_count} published of {c.products_count} products
                                </p>
                                <div className="mt-3 flex gap-2">
                                    <Button size="sm" variant="secondary" onClick={() => setEditing(c)}>
                                        Edit
                                    </Button>
                                    <Button size="sm" variant="ghost" asChild>
                                        <a href={`/admin/products?category=${c.id}`}>Products</a>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </Panel>
                ))}
            </div>
            <Dialog open={editing !== null} onOpenChange={(o) => !o && setEditing(null)}>
                {editing !== null && (
                    <DialogContent title={editing === 'new' ? 'New category' : `Edit ${editing.name}`} className="max-w-2xl">
                        <CategoryForm category={editing === 'new' ? null : editing} motifs={motifs} onDone={() => setEditing(null)} />
                    </DialogContent>
                )}
            </Dialog>
        </>
    );
}

function CategoryForm({ category, motifs, onDone }: { category: Category | null; motifs: string[]; onDone: () => void }) {
    const form = useForm({
        name: category?.name ?? '',
        slug: category?.slug ?? '',
        tagline: category?.tagline ?? '',
        description: category?.description ?? '',
        tint: category?.tint ?? '#EDE5D8',
        motif: category?.motif ?? 'leaf',
        sort_order: category?.sort_order ?? 0,
        is_active: category?.is_active ?? true,
        seo_title: category?.seo_title ?? '',
        seo_description: category?.seo_description ?? '',
        name_fr: category?.name_fr ?? '',
        tagline_fr: category?.tagline_fr ?? '',
        description_fr: category?.description_fr ?? '',
        seo_title_fr: category?.seo_title_fr ?? '',
        seo_description_fr: category?.seo_description_fr ?? '',
    });
    const c = useContentLanguage();
    const err = (key: string) => (form.errors as Record<string, string | undefined>)[key];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (category) form.put(`/admin/categories/${category.id}`, opts);
        else form.post('/admin/categories', opts);
    };

    return (
        <form onSubmit={submit} noValidate className="space-y-4">
            <ContentLanguageSwitch value={c.lang} onChange={c.setLang} frenchErrors={hasFrenchErrors(form.errors)} />
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label={c.label('Name')} name={c.field('name')} error={err(c.field('name'))} required={!c.fr} optional={c.fr}>
                    {(p) => <Input {...p} value={form.data[c.field('name')]} onChange={(e) => form.setData(c.field('name'), e.target.value)} placeholder={c.source(form.data.name)} />}
                </Field>
                <Field label="Web address" name="slug" error={form.errors.slug} hint="Changing it breaks existing links." optional>
                    {(p) => <Input {...p} value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} />}
                </Field>
            </div>
            <Field label={c.label('Tagline')} name={c.field('tagline')} error={err(c.field('tagline'))} optional>
                {(p) => (
                    <Input {...p} value={form.data[c.field('tagline')]} onChange={(e) => form.setData(c.field('tagline'), e.target.value)} placeholder={c.source(form.data.tagline)} maxLength={160} />
                )}
            </Field>
            <Field
                label={c.label('Description')}
                name={c.field('description')}
                error={err(c.field('description'))}
                hint="Shown at the top of the category page. Describe the range without health claims."
                optional
            >
                {(p) => (
                    <Textarea {...p} rows={3} value={form.data[c.field('description')]} onChange={(e) => form.setData(c.field('description'), e.target.value)} placeholder={c.source(form.data.description)} />
                )}
            </Field>
            <div className="grid gap-4 sm:grid-cols-3">
                <Field label="Colour" name="tint" error={form.errors.tint}>
                    {(p) => (
                        <div className="flex gap-2">
                            <input type="color" aria-label="Pick colour" value={form.data.tint} onChange={(e) => form.setData('tint', e.target.value.toUpperCase())} className="h-11 w-12 rounded border border-line" />
                            <Input {...p} value={form.data.tint} onChange={(e) => form.setData('tint', e.target.value)} maxLength={7} />
                        </div>
                    )}
                </Field>
                <Field label="Motif" name="motif" error={form.errors.motif}>
                    {(p) => (
                        <Select {...p} value={form.data.motif} onChange={(e) => form.setData('motif', e.target.value)}>
                            {motifs.map((m) => (
                                <option key={m} value={m}>
                                    {m[0]?.toUpperCase() + m.slice(1)}
                                </option>
                            ))}
                        </Select>
                    )}
                </Field>
                <Field label="Position" name="sort_order" error={form.errors.sort_order}>
                    {(p) => <Input {...p} type="number" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', Number(e.target.value))} />}
                </Field>
            </div>
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
            <div className="flex items-center gap-3">
                <Switch id="cat-active" checked={form.data.is_active} onCheckedChange={(v) => form.setData('is_active', v)} />
                <Label htmlFor="cat-active" className="font-normal">
                    Visible in the shop
                </Label>
            </div>
            <div className="flex flex-wrap justify-between gap-2 pt-2">
                {category && category.products_count === 0 ? (
                    <ConfirmDialog
                        trigger={
                            <Button type="button" variant="destructive-outline">
                                Delete
                            </Button>
                        }
                        title="Delete this category?"
                        description="It has no products. Its shop page will stop working."
                        confirmLabel="Delete category"
                        onConfirm={() => router.delete(`/admin/categories/${category.id}`, { onSuccess: onDone })}
                    />
                ) : (
                    <span />
                )}
                <Button type="submit" loading={form.processing}>
                    Save category
                </Button>
            </div>
        </form>
    );
}
