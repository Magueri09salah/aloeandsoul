import { Link, router } from '@inertiajs/react';
import { Banknote, Plus, Truck } from 'lucide-react';
import { useState, type ReactNode } from 'react';

import type { Breadcrumb, ProductCard as ProductCardData, ProductDetail } from '@/types';

import { Button } from '@/components/ui/button';
import { Price } from '@/components/ui/price';
import { QuantityStepper } from '@/components/ui/quantity-stepper';
import { SafeHtml } from '@/components/ui/safe-html';
import { Breadcrumbs } from '@/components/store/breadcrumbs';
import { useCartUi } from '@/components/store/cart-context';
import { MOTIF_BY_CATEGORY, ProductCard } from '@/components/store/product-card';
import { ProductGallery } from '@/components/store/product-gallery';
import { ProductPlaceholder } from '@/components/store/product-placeholder';
import { SectionHeading } from '@/components/store/section-heading';
import { useShared } from '@/hooks/use-shared';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';

interface ProductPageProps {
    product: ProductDetail;
    related: ProductCardData[];
    breadcrumbs: Breadcrumb[];
    delivery: {
        cod_instructions: string;
        info: string;
        min_fee_minor: number | null;
        zones: { name: string; fee_minor: number; free_over_minor: number | null; estimate: string | null }[];
    };
}

export default function ProductShow({ product, related, breadcrumbs, delivery }: ProductPageProps) {
    const { store, errors } = useShared();
    const orderingEnabled = store.ordering_enabled;
    const cart = useCartUi();
    const firstAvailable = product.variants.find((v) => v.in_stock) ?? product.variants[0];
    const [variantId, setVariantId] = useState(firstAvailable?.id ?? null);
    const [quantity, setQuantity] = useState(1);
    const [adding, setAdding] = useState(false);

    const variant = product.variants.find((v) => v.id === variantId) ?? firstAvailable;
    const hasOptions = product.variants.length > 1;
    const size = product.specifications.find((s) => s.label.toLowerCase() === 'size')?.value;
    const reference = [variant?.sku ? `Ref. ${variant.sku}` : null, size].filter(Boolean).join(' – ');
    const hasDetails = product.specifications.length > 0 || Boolean(variant?.sku);

    const add = () => {
        if (!variant) return;
        router.post(
            '/cart/items',
            { variant_id: variant.id, quantity },
            {
                preserveScroll: true,
                onStart: () => setAdding(true),
                onFinish: () => setAdding(false),
                onSuccess: (page) => {
                    if (!(page.props.errors as Record<string, string>).quantity) {
                        setQuantity(1);
                        cart.open();
                    }
                },
            },
        );
    };

    return (
        <>
            <div className="container-page pt-6 lg:pt-8">
                <Breadcrumbs items={breadcrumbs} />
            </div>

            {/* Mobile: images, purchase panel, information. Desktop: information under the images, panel alongside. */}
            <div className="container-page grid gap-10 pt-6 pb-16 lg:grid-cols-12 lg:gap-x-12">
                <div className="lg:col-span-7">
                    <ProductGallery
                        images={product.images}
                        tint={product.category.tint}
                        label={`${product.name} images`}
                        placeholder={<ProductPlaceholder tint={product.category.tint} motif={MOTIF_BY_CATEGORY[product.category.slug]} />}
                    />
                </div>

                {/* Purchase panel */}
                <section aria-labelledby="product-title" className="lg:col-span-5 lg:col-start-8 lg:row-span-2 lg:row-start-1">
                    <div className="lg:sticky lg:top-28">
                        <Link href={`/shop/${product.category.slug}`} className="text-sm text-dune-deep hover:underline hover:underline-offset-4">
                            {product.category.name}
                        </Link>
                        <h1 id="product-title" className="mt-2 font-display text-display-sm">
                            {product.name}
                        </h1>
                        <p className="mt-4 text-xl">
                            {variant ? <Price amount={variant.price_minor} regular={variant.regular_price_minor} /> : null}
                        </p>
                        {reference && <p className="mt-1 text-sm text-stone">{reference}</p>}
                        {product.summary && <p className="mt-4 text-lg text-stone">{product.summary}</p>}

                        {hasOptions && (
                            <fieldset className="mt-8">
                                <legend className="mb-3 text-sm font-medium">
                                    {product.option_label ?? 'Option'}: <span className="font-normal text-stone">{variant?.name}</span>
                                </legend>
                                <div className="flex flex-wrap gap-2">
                                    {product.variants.map((v) => (
                                        <label
                                            key={v.id}
                                            className={cn(
                                                'relative inline-flex h-11 cursor-pointer items-center rounded-full border px-5 text-[0.95rem] transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-aloe',
                                                v.id === variant?.id ? 'border-ink bg-ink text-ivory' : 'border-line bg-white hover:border-ink',
                                                !v.in_stock && 'text-stone line-through decoration-1',
                                            )}
                                        >
                                            <input
                                                type="radio"
                                                name="variant"
                                                value={v.id}
                                                checked={v.id === variant?.id}
                                                onChange={() => {
                                                    setVariantId(v.id);
                                                    setQuantity(1);
                                                }}
                                                className="sr-only"
                                            />
                                            {v.name}
                                            {!v.in_stock && <span className="sr-only"> (out of stock)</span>}
                                        </label>
                                    ))}
                                </div>
                            </fieldset>
                        )}

                        <div className="mt-8">
                            {variant?.in_stock ? (
                                <>
                                    <div className="flex flex-wrap items-center gap-3">
                                        <QuantityStepper label="Quantity" value={quantity} max={variant.max_quantity} onChange={setQuantity} />
                                        <Button size="lg" className="min-w-52 flex-1" loading={adding} onClick={add} disabled={!orderingEnabled}>
                                            Add to cart
                                        </Button>
                                    </div>
                                    {variant.low_stock && <p className="mt-3 text-sm text-dune-deep">Only a few left.</p>}
                                    {errors.quantity && (
                                        <p className="mt-3 text-sm text-clay" role="alert">
                                            {errors.quantity}
                                        </p>
                                    )}
                                    {!orderingEnabled && <p className="mt-3 text-sm text-stone">Online ordering is paused at the moment.</p>}
                                </>
                            ) : (
                                <p className="rounded-(--radius-control) bg-sand px-4 py-3 text-stone">
                                    {hasOptions ? 'This option is out of stock.' : 'This product is out of stock.'} Please check back soon or{' '}
                                    <Link href="/contact" className="underline underline-offset-4">
                                        contact us
                                    </Link>
                                    .
                                </p>
                            )}
                        </div>

                        <div className="mt-8 divide-y divide-line border-y border-line text-[0.95rem]">
                            <div className="flex gap-4 py-4">
                                <Banknote className="mt-0.5 size-5 shrink-0 text-dune" strokeWidth={1.5} aria-hidden="true" />
                                <div>
                                    <p className="font-medium">Cash on delivery</p>
                                    <p className="text-stone">{delivery.cod_instructions}</p>
                                </div>
                            </div>
                            {delivery.zones.length > 0 && (
                                <details className="group py-4">
                                    <summary className="flex cursor-pointer list-none gap-4 [&::-webkit-details-marker]:hidden">
                                        <Truck className="mt-0.5 size-5 shrink-0 text-dune" strokeWidth={1.5} aria-hidden="true" />
                                        <span className="flex-1">
                                            <span className="block font-medium">Delivery</span>
                                            <span className="text-stone">
                                                {delivery.min_fee_minor !== null && `From ${formatMoney(delivery.min_fee_minor, store.currency)}. `}
                                                <span className="underline underline-offset-4 group-open:hidden">See delivery areas</span>
                                            </span>
                                        </span>
                                    </summary>
                                    <ul className="mt-3 ml-9 space-y-1.5 text-stone">
                                        {delivery.zones.map((z) => (
                                            <li key={z.name} className="flex justify-between gap-4">
                                                <span>
                                                    {z.name}
                                                    {z.estimate && <span className="text-stone/80"> · {z.estimate}</span>}
                                                </span>
                                                <span className="text-right tabular-nums">
                                                    {formatMoney(z.fee_minor, store.currency)}
                                                    {z.free_over_minor !== null && (
                                                        <span className="block text-xs">free over {formatMoney(z.free_over_minor, store.currency)}</span>
                                                    )}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                    {delivery.info && <p className="mt-3 ml-9 text-stone">{delivery.info}</p>}
                                </details>
                            )}
                        </div>

                    </div>
                </section>

                {/* Product information */}
                {(product.sections.length > 0 || hasDetails) && (
                    <section aria-label="Product information" className="border-t border-line lg:col-span-7 lg:row-start-2">
                        {product.sections.map((section, i) => (
                            <InfoSection key={section.id} title={section.title} open={i === 0}>
                                <SafeHtml html={section.html} className="prose-compact text-ink/90" />
                            </InfoSection>
                        ))}
                        {hasDetails && (
                            <InfoSection title="Details" open={product.sections.length === 0}>
                                <dl className="max-w-[70ch] divide-y divide-line border-y border-line">
                                    {product.specifications.map((s) => (
                                        <div key={s.label} className="grid grid-cols-3 gap-4 py-3 text-[0.95rem]">
                                            <dt className="text-stone">{s.label}</dt>
                                            <dd className="col-span-2">{s.value}</dd>
                                        </div>
                                    ))}
                                    {variant?.sku && (
                                        <div className="grid grid-cols-3 gap-4 py-3 text-[0.95rem]">
                                            <dt className="text-stone">Reference</dt>
                                            <dd className="col-span-2">{variant.sku}</dd>
                                        </div>
                                    )}
                                </dl>
                            </InfoSection>
                        )}
                    </section>
                )}
            </div>

            {related.length > 0 && (
                <section className="container-page border-t border-line pt-16">
                    <SectionHeading title={`More from ${product.category.name}`} link={{ href: `/shop/${product.category.slug}`, label: 'See all' }} />
                    <div className="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                        {related.map((p) => (
                            <ProductCard key={p.id} product={p} />
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}

/** One collapsible block of product information (native <details>, so it works before the page script loads). */
function InfoSection({ title, open, children }: { title: string; open: boolean; children: ReactNode }) {
    return (
        <details className="group border-b border-line" open={open}>
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 py-5 [&::-webkit-details-marker]:hidden">
                <h2 className="font-display text-2xl">{title}</h2>
                <Plus className="size-5 shrink-0 text-dune transition-transform duration-200 group-open:rotate-45" strokeWidth={1.5} aria-hidden="true" />
            </summary>
            <div className="pb-8">{children}</div>
        </details>
    );
}
