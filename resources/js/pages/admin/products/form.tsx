import { router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ExternalLink, Plus, Trash2, Upload } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import type { ImageData } from '@/types';

import { PageHeader, Panel, ProductStatusBadge } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { Checkbox, Switch } from '@/components/ui/checkbox';
import { ConfirmDialog } from '@/components/ui/dialog';
import { Field, FormErrorSummary } from '@/components/ui/field';
import { Input, Label, Select, Textarea } from '@/components/ui/input';

interface VariantForm {
    id: number | null;
    name: string;
    sku: string;
    price: string;
    sale_price: string;
    stock: number;
    track_stock: boolean;
    low_stock_threshold: number | string | null;
    is_active: boolean;
}

interface ProductData {
    id: number;
    name: string;
    slug: string;
    category_id: number;
    summary: string | null;
    description: string | null;
    benefits: string | null;
    usage: string | null;
    ingredients: string | null;
    nutrition: string | null;
    specifications: { label: string; value: string }[];
    option_label: string | null;
    is_featured: boolean;
    is_bestseller: boolean;
    sort_order: number;
    seo_title: string | null;
    seo_description: string | null;
    status: string;
    status_label: string;
    url: string | null;
    variants: VariantForm[];
    images: (ImageData & { id: number })[];
    blockers: string[];
    has_orders: boolean;
}

interface Props {
    product: ProductData | null;
    categories: { id: number; name: string }[];
    defaultCategoryId: number | null;
    currency: string;
}

const emptyVariant = (): VariantForm => ({
    id: null,
    name: '',
    sku: '',
    price: '',
    sale_price: '',
    stock: 0,
    track_stock: true,
    low_stock_threshold: '',
    is_active: true,
});

export default function ProductForm({ product, categories, defaultCategoryId, currency }: Props) {
    const form = useForm({
        name: product?.name ?? '',
        slug: product?.slug ?? '',
        category_id: String(product?.category_id ?? defaultCategoryId ?? categories[0]?.id ?? ''),
        summary: product?.summary ?? '',
        description: product?.description ?? '',
        benefits: product?.benefits ?? '',
        usage: product?.usage ?? '',
        ingredients: product?.ingredients ?? '',
        nutrition: product?.nutrition ?? '',
        specifications: product?.specifications ?? [],
        option_label: product?.option_label ?? '',
        is_featured: product?.is_featured ?? false,
        is_bestseller: product?.is_bestseller ?? false,
        sort_order: product?.sort_order ?? 0,
        seo_title: product?.seo_title ?? '',
        seo_description: product?.seo_description ?? '',
        variants: product?.variants.length
            ? product.variants.map((v) => ({ ...v, name: v.name ?? '', sku: v.sku ?? '', low_stock_threshold: v.low_stock_threshold ?? '' }))
            : [emptyVariant()],
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (product) {
            form.put(`/admin/products/${product.id}`, { preserveScroll: true });
        } else {
            form.post('/admin/products');
        }
    };

    const err = (key: string) => (form.errors as Record<string, string | undefined>)[key];
    const setVariant = (i: number, patch: Partial<VariantForm>) =>
        form.setData(
            'variants',
            form.data.variants.map((v, j) => (j === i ? { ...v, ...patch } : v)),
        );
    const multi = form.data.variants.length > 1;

    return (
        <>
            <PageHeader
                back={{ href: '/admin/products', label: 'Products' }}
                title={product ? product.name : 'New product'}
                description={
                    product && (
                        <span className="flex flex-wrap items-center gap-2">
                            <ProductStatusBadge status={product.status} label={product.status_label} />
                            {product.url && (
                                <a href={product.url} target="_blank" rel="noopener" className="inline-flex items-center gap-1 text-sm underline underline-offset-4">
                                    View in shop <ExternalLink className="size-3.5" aria-hidden="true" />
                                </a>
                            )}
                        </span>
                    )
                }
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <form onSubmit={submit} noValidate className="space-y-6 lg:col-span-2" id="product-form">
                    <FormErrorSummary errors={{ general: err('general') }} />

                    <Panel title="Basics">
                        <div className="space-y-5">
                            <Field label="Name" name="name" error={err('name')} required>
                                {(p) => <Input {...p} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} maxLength={160} />}
                            </Field>
                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field label="Category" name="category_id" error={err('category_id')} required>
                                    {(p) => (
                                        <Select {...p} value={form.data.category_id} onChange={(e) => form.setData('category_id', e.target.value)}>
                                            {categories.map((c) => (
                                                <option key={c.id} value={c.id}>
                                                    {c.name}
                                                </option>
                                            ))}
                                        </Select>
                                    )}
                                </Field>
                                <Field label="Web address" name="slug" error={err('slug')} hint="Leave empty to create it from the name." optional>
                                    {(p) => <Input {...p} value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} maxLength={180} placeholder="aloe-vera-gel" />}
                                </Field>
                            </div>
                            <Field label="Short summary" name="summary" error={err('summary')} hint="One line shown under the name. Describe what it is, without health claims." optional>
                                {(p) => <Input {...p} value={form.data.summary} onChange={(e) => form.setData('summary', e.target.value)} maxLength={255} />}
                            </Field>
                            <Field
                                label="Description"
                                name="description"
                                error={err('description')}
                                hint="Use the official product information. You can use **bold**, *italic* and lists (- item). Do not promise to treat, cure or prevent any condition."
                            >
                                {(p) => <Textarea {...p} rows={8} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />}
                            </Field>
                        </div>
                    </Panel>

                    <Panel
                        title="Product information"
                        description="Each filled field becomes its own section on the product page, after the description. Same formatting as the description; tables are written as | Name | Amount | rows."
                    >
                        <div className="space-y-5">
                            <Field label="Benefits" name="benefits" error={err('benefits')} optional>
                                {(p) => <Textarea {...p} rows={6} value={form.data.benefits} onChange={(e) => form.setData('benefits', e.target.value)} />}
                            </Field>
                            <Field label="How to use" name="usage" error={err('usage')} hint="Dose, when to use it and precautions." optional>
                                {(p) => <Textarea {...p} rows={5} value={form.data.usage} onChange={(e) => form.setData('usage', e.target.value)} />}
                            </Field>
                            <Field label="Ingredients" name="ingredients" error={err('ingredients')} hint="Copy the list from the packaging. Put allergens in **bold**." optional>
                                {(p) => <Textarea {...p} rows={5} value={form.data.ingredients} onChange={(e) => form.setData('ingredients', e.target.value)} />}
                            </Field>
                            <Field label="Nutrition information" name="nutrition" error={err('nutrition')} optional>
                                {(p) => <Textarea {...p} rows={6} value={form.data.nutrition} onChange={(e) => form.setData('nutrition', e.target.value)} className="font-mono text-sm" />}
                            </Field>

                            <fieldset>
                                <legend className="text-sm font-medium">Details table</legend>
                                <p className="mt-1 text-sm text-stone">For example Volume: 1 litre, or Flavour: Vanilla.</p>
                                <div className="mt-3 space-y-2">
                                    {form.data.specifications.map((s, i) => (
                                        <div key={i} className="flex gap-2">
                                            <Input
                                                aria-label={`Detail ${i + 1} label`}
                                                aria-invalid={err(`specifications.${i}.label`) ? true : undefined}
                                                placeholder="Label"
                                                value={s.label}
                                                onChange={(e) => form.setData('specifications', form.data.specifications.map((x, j) => (j === i ? { ...x, label: e.target.value } : x)))}
                                                className="max-w-48"
                                            />
                                            <Input
                                                aria-label={`Detail ${i + 1} value`}
                                                aria-invalid={err(`specifications.${i}.value`) ? true : undefined}
                                                placeholder="Value"
                                                value={s.value}
                                                onChange={(e) => form.setData('specifications', form.data.specifications.map((x, j) => (j === i ? { ...x, value: e.target.value } : x)))}
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Remove detail ${i + 1}`}
                                                onClick={() => form.setData('specifications', form.data.specifications.filter((_, j) => j !== i))}
                                            >
                                                <Trash2 aria-hidden="true" />
                                            </Button>
                                        </div>
                                    ))}
                                    {form.data.specifications.length < 20 && (
                                        <Button type="button" variant="ghost" size="sm" onClick={() => form.setData('specifications', [...form.data.specifications, { label: '', value: '' }])}>
                                            <Plus aria-hidden="true" /> Add a detail
                                        </Button>
                                    )}
                                </div>
                            </fieldset>
                        </div>
                    </Panel>

                    <Panel
                        title="Price and stock"
                        description={`Prices in ${currency}. Changing stock here is recorded in the stock history.`}
                        actions={
                            <Button type="button" size="sm" variant="secondary" onClick={() => form.setData('variants', [...form.data.variants, emptyVariant()])}>
                                <Plus aria-hidden="true" /> Add option
                            </Button>
                        }
                    >
                        {multi && (
                            <Field label="What do the options differ by?" name="option_label" error={err('option_label')} hint="For example Flavour, Size or Volume." className="mb-5 max-w-xs">
                                {(p) => <Input {...p} value={form.data.option_label} onChange={(e) => form.setData('option_label', e.target.value)} maxLength={40} />}
                            </Field>
                        )}
                        <div className="space-y-4">
                            {form.data.variants.map((v, i) => (
                                <fieldset key={v.id ?? `new-${i}`} className="rounded-md border border-line p-4">
                                    <legend className="px-1 text-sm font-medium">{multi ? v.name || `Option ${i + 1}` : 'Price and stock'}</legend>
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                        {multi && (
                                            <Field label="Option name" name={`variants.${i}.name`} error={err(`variants.${i}.name`)} required className="sm:col-span-2">
                                                {(p) => <Input {...p} value={v.name} onChange={(e) => setVariant(i, { name: e.target.value })} placeholder="e.g. Vanilla" maxLength={80} />}
                                            </Field>
                                        )}
                                        <Field label="SKU" name={`variants.${i}.sku`} error={err(`variants.${i}.sku`)} optional className={multi ? 'sm:col-span-2' : undefined}>
                                            {(p) => <Input {...p} value={v.sku} onChange={(e) => setVariant(i, { sku: e.target.value })} maxLength={64} />}
                                        </Field>
                                        <Field label="Price" name={`variants.${i}.price`} error={err(`variants.${i}.price`)}>
                                            {(p) => <Input {...p} inputMode="decimal" value={v.price} onChange={(e) => setVariant(i, { price: e.target.value })} placeholder="249.00" />}
                                        </Field>
                                        <Field label="Sale price" name={`variants.${i}.sale_price`} error={err(`variants.${i}.sale_price`)} optional>
                                            {(p) => <Input {...p} inputMode="decimal" value={v.sale_price} onChange={(e) => setVariant(i, { sale_price: e.target.value })} />}
                                        </Field>
                                        <Field label="In stock" name={`variants.${i}.stock`} error={err(`variants.${i}.stock`)}>
                                            {(p) => (
                                                <Input
                                                    {...p}
                                                    type="number"
                                                    min={0}
                                                    value={v.stock}
                                                    disabled={!v.track_stock}
                                                    onChange={(e) => setVariant(i, { stock: Math.max(0, Number.parseInt(e.target.value || '0', 10)) })}
                                                />
                                            )}
                                        </Field>
                                        <Field label="Low stock alert at" name={`variants.${i}.low_stock_threshold`} error={err(`variants.${i}.low_stock_threshold`)} optional>
                                            {(p) => (
                                                <Input
                                                    {...p}
                                                    type="number"
                                                    min={0}
                                                    value={v.low_stock_threshold ?? ''}
                                                    onChange={(e) => setVariant(i, { low_stock_threshold: e.target.value })}
                                                    placeholder="Default"
                                                />
                                            )}
                                        </Field>
                                    </div>
                                    <div className="mt-4 flex flex-wrap items-center gap-x-6 gap-y-3">
                                        <div className="flex items-center gap-2">
                                            <Checkbox id={`track-${i}`} checked={v.track_stock} onCheckedChange={(c) => setVariant(i, { track_stock: c === true })} />
                                            <Label htmlFor={`track-${i}`} className="font-normal">
                                                Track stock
                                            </Label>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Checkbox id={`active-${i}`} checked={v.is_active} onCheckedChange={(c) => setVariant(i, { is_active: c === true })} />
                                            <Label htmlFor={`active-${i}`} className="font-normal">
                                                Available for sale
                                            </Label>
                                        </div>
                                        {multi && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="ml-auto text-clay"
                                                onClick={() => form.setData('variants', form.data.variants.filter((_, j) => j !== i))}
                                            >
                                                <Trash2 aria-hidden="true" /> Remove option
                                            </Button>
                                        )}
                                    </div>
                                </fieldset>
                            ))}
                        </div>
                    </Panel>

                    <Panel title="Search engines" description="Optional. By default the product name and summary are used.">
                        <div className="space-y-5">
                            <Field label="Page title" name="seo_title" error={err('seo_title')} hint={`${form.data.seo_title.length}/70 characters`} optional>
                                {(p) => <Input {...p} value={form.data.seo_title} onChange={(e) => form.setData('seo_title', e.target.value)} maxLength={70} />}
                            </Field>
                            <Field label="Description" name="seo_description" error={err('seo_description')} hint={`${form.data.seo_description.length}/170 characters`} optional>
                                {(p) => <Textarea {...p} rows={2} value={form.data.seo_description} onChange={(e) => form.setData('seo_description', e.target.value)} maxLength={170} />}
                            </Field>
                        </div>
                    </Panel>

                    <div className="sticky bottom-0 z-10 -mx-4 flex justify-end gap-2 border-t border-line bg-panel/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-lg sm:border">
                        <Button type="submit" loading={form.processing}>
                            {product ? 'Save product' : 'Create draft'}
                        </Button>
                    </div>
                </form>

                <div className="space-y-6">
                    {product && <Visibility product={product} />}

                    <Panel title="Merchandising">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <Label htmlFor="featured" className="font-normal">
                                    Featured on the home page
                                </Label>
                                <Switch id="featured" checked={form.data.is_featured} onCheckedChange={(v) => form.setData('is_featured', v)} />
                            </div>
                            <div className="flex items-center justify-between gap-3">
                                <Label htmlFor="bestseller" className="font-normal">
                                    Show “Bestseller” label
                                </Label>
                                <Switch id="bestseller" checked={form.data.is_bestseller} onCheckedChange={(v) => form.setData('is_bestseller', v)} />
                            </div>
                            <Field label="Position" name="sort_order" error={err('sort_order')} hint="Lower numbers appear first.">
                                {(p) => <Input {...p} type="number" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', Number(e.target.value))} className="max-w-28" />}
                            </Field>
                            <p className="text-xs text-stone">Saved with the product.</p>
                        </div>
                    </Panel>

                    {product ? (
                        <ImageManager product={product} />
                    ) : (
                        <Panel title="Images">
                            <p className="text-sm text-stone">Create the draft first, then add images.</p>
                        </Panel>
                    )}

                    {product && !product.has_orders && (
                        <Panel title="Delete">
                            <p className="text-sm text-stone">Deleting cannot be undone. Products that have been ordered can only be archived.</p>
                            <ConfirmDialog
                                trigger={
                                    <Button variant="destructive-outline" size="sm" className="mt-3">
                                        Delete product
                                    </Button>
                                }
                                title="Delete this product?"
                                description={`“${product.name}” and its images will be removed permanently.`}
                                confirmLabel="Delete product"
                                onConfirm={() => router.delete(`/admin/products/${product.id}`)}
                            />
                        </Panel>
                    )}
                </div>
            </div>
        </>
    );
}

function Visibility({ product }: { product: ProductData }) {
    const status = (action: string) => router.post(`/admin/products/${product.id}/status`, { action }, { preserveScroll: true });

    return (
        <Panel title="Visibility">
            {product.status === 'draft' && (
                <>
                    {product.blockers.length > 0 ? (
                        <>
                            <p className="text-sm">Before publishing:</p>
                            <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-stone">
                                {product.blockers.map((b) => (
                                    <li key={b}>{b}</li>
                                ))}
                            </ul>
                            <p className="mt-3 text-xs text-stone">Save your changes first; this list updates after saving.</p>
                        </>
                    ) : (
                        <p className="text-sm text-stone">Ready to publish.</p>
                    )}
                    <Button className="mt-4 w-full" disabled={product.blockers.length > 0} onClick={() => status('publish')}>
                        Publish
                    </Button>
                </>
            )}
            {product.status === 'published' && (
                <div className="space-y-2">
                    <p className="text-sm text-stone">Visible in the shop.</p>
                    <Button variant="secondary" className="w-full" onClick={() => status('unpublish')}>
                        Move back to drafts
                    </Button>
                    <ConfirmDialog
                        trigger={
                            <Button variant="ghost" className="w-full">
                                Archive (discontinued)
                            </Button>
                        }
                        title="Archive this product?"
                        description="It leaves the shop, and its page tells visitors it is no longer available. Past orders are not affected."
                        confirmLabel="Archive"
                        onConfirm={() => status('archive')}
                    />
                </div>
            )}
            {product.status === 'archived' && (
                <Button variant="secondary" className="w-full" onClick={() => status('restore')}>
                    Restore as draft
                </Button>
            )}
        </Panel>
    );
}

function ImageManager({ product }: { product: ProductData }) {
    const upload = useForm<{ images: File[] }>({ images: [] });
    const [images, setImages] = useState(product.images.map((i) => ({ id: i.id, src: i.src, alt: i.alt })));
    const [dirty, setDirty] = useState(false);

    // Keep local order/alt edits in sync after uploads or deletions.
    const serverIds = product.images.map((i) => i.id).join(',');
    const [syncedIds, setSyncedIds] = useState(serverIds);
    if (serverIds !== syncedIds) {
        setSyncedIds(serverIds);
        setImages(product.images.map((i) => ({ id: i.id, src: i.src, alt: i.alt })));
        setDirty(false);
    }

    const move = (i: number, d: -1 | 1) => {
        const next = [...images];
        const [item] = next.splice(i, 1);
        if (!item) return;
        next.splice(i + d, 0, item);
        setImages(next);
        setDirty(true);
    };

    const uploadErrors = Object.entries(upload.errors).map(([, m]) => m);

    return (
        <Panel title="Images" description="JPG, PNG or WebP, at least 400 px wide. The first image is the main one.">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    upload.post(`/admin/products/${product.id}/images`, { forceFormData: true, preserveScroll: true, onSuccess: () => upload.reset() });
                }}
            >
                <label className="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-md border border-dashed border-stone/40 px-4 py-6 text-center text-sm hover:border-ink">
                    <Upload className="size-5 text-stone" aria-hidden="true" />
                    <span>{upload.data.images.length ? `${upload.data.images.length} file(s) selected` : 'Choose images'}</span>
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        className="sr-only"
                        onChange={(e) => upload.setData('images', Array.from(e.target.files ?? []))}
                    />
                </label>
                {uploadErrors.length > 0 && (
                    <ul className="mt-2 text-sm text-clay" role="alert">
                        {uploadErrors.map((m) => (
                            <li key={m}>{m}</li>
                        ))}
                    </ul>
                )}
                <Button type="submit" size="sm" className="mt-3 w-full" loading={upload.processing} disabled={upload.data.images.length === 0}>
                    Upload
                </Button>
            </form>

            {images.length > 0 && (
                <ul className="mt-5 space-y-3">
                    {images.map((img, i) => (
                        <li key={img.id} className="flex gap-3">
                            <img src={img.src} alt="" className="size-16 shrink-0 rounded object-cover" />
                            <div className="min-w-0 flex-1">
                                <label htmlFor={`alt-${img.id}`} className="text-xs text-stone">
                                    Description for screen readers{i === 0 ? ' (main image)' : ''}
                                </label>
                                <Input
                                    id={`alt-${img.id}`}
                                    value={img.alt}
                                    maxLength={160}
                                    onChange={(e) => {
                                        setImages(images.map((x) => (x.id === img.id ? { ...x, alt: e.target.value } : x)));
                                        setDirty(true);
                                    }}
                                    className="mt-1 h-9 text-sm"
                                />
                                <div className="mt-1 flex gap-1">
                                    <Button type="button" variant="ghost" size="icon-sm" disabled={i === 0} onClick={() => move(i, -1)} aria-label="Move up">
                                        <ArrowUp aria-hidden="true" />
                                    </Button>
                                    <Button type="button" variant="ghost" size="icon-sm" disabled={i === images.length - 1} onClick={() => move(i, 1)} aria-label="Move down">
                                        <ArrowDown aria-hidden="true" />
                                    </Button>
                                    <ConfirmDialog
                                        trigger={
                                            <Button type="button" variant="ghost" size="icon-sm" aria-label="Delete image" className="ml-auto text-clay">
                                                <Trash2 aria-hidden="true" />
                                            </Button>
                                        }
                                        title="Delete this image?"
                                        description="The image file is removed permanently."
                                        confirmLabel="Delete image"
                                        onConfirm={() => router.delete(`/admin/products/${product.id}/images/${img.id}`, { preserveScroll: true })}
                                    />
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            {dirty && (
                <Button
                    size="sm"
                    variant="secondary"
                    className="mt-4 w-full"
                    onClick={() => router.put(`/admin/products/${product.id}/images`, { images: images.map(({ id, alt }) => ({ id, alt })) }, { preserveScroll: true })}
                >
                    Save image order and descriptions
                </Button>
            )}
        </Panel>
    );
}
