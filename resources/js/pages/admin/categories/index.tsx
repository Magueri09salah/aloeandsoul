import { router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { Motif } from '@/components/brand/motif';
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
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (category) form.put(`/admin/categories/${category.id}`, opts);
        else form.post('/admin/categories', opts);
    };

    return (
        <form onSubmit={submit} noValidate className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Name" name="name" error={form.errors.name} required>
                    {(p) => <Input {...p} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />}
                </Field>
                <Field label="Web address" name="slug" error={form.errors.slug} hint="Changing it breaks existing links." optional>
                    {(p) => <Input {...p} value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} />}
                </Field>
            </div>
            <Field label="Tagline" name="tagline" error={form.errors.tagline} optional>
                {(p) => <Input {...p} value={form.data.tagline} onChange={(e) => form.setData('tagline', e.target.value)} maxLength={160} />}
            </Field>
            <Field label="Description" name="description" error={form.errors.description} hint="Shown at the top of the category page. Describe the range without health claims." optional>
                {(p) => <Textarea {...p} rows={3} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />}
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
                <Field label="Search title" name="seo_title" error={form.errors.seo_title} optional>
                    {(p) => <Input {...p} value={form.data.seo_title} onChange={(e) => form.setData('seo_title', e.target.value)} maxLength={70} />}
                </Field>
                <Field label="Search description" name="seo_description" error={form.errors.seo_description} optional>
                    {(p) => <Input {...p} value={form.data.seo_description} onChange={(e) => form.setData('seo_description', e.target.value)} maxLength={170} />}
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
