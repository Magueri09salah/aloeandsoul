import { Link, router } from '@inertiajs/react';
import { Banknote, Minus, Plus, Truck } from 'lucide-react';
import { useState, type ReactNode } from 'react';

import type { Breadcrumb, ProductCard as ProductCardData, ProductDetail } from '@/types';

import { Button } from '@/components/ui/button';
import { Price } from '@/components/ui/price';
import { QuantityStepper } from '@/components/ui/quantity-stepper';
import { SafeHtml } from '@/components/ui/safe-html';
import { Breadcrumbs } from '@/components/store/breadcrumbs';
import { useCartUi } from '@/components/store/cart-context';
import { MOTIF_BY_CATEGORY } from '@/components/store/product-card';
import { ProductGallery } from '@/components/store/product-gallery';
import { ProductPlaceholder } from '@/components/store/product-placeholder';
import { ProductRail } from '@/components/store/product-rail';
import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';
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
    const { t, to, money } = useI18n();
    const orderingEnabled = store.ordering_enabled;
    const cart = useCartUi();
    const firstAvailable = product.variants.find((v) => v.in_stock) ?? product.variants[0];
    const [variantId, setVariantId] = useState(firstAvailable?.id ?? null);
    const [quantity, setQuantity] = useState(1);
    const [adding, setAdding] = useState(false);

    const variant = product.variants.find((v) => v.id === variantId) ?? firstAvailable;
    const hasOptions = product.variants.length > 1;
    const reference = [variant?.sku ? t('Ref. :sku', { sku: variant.sku }) : null, product.size]
        .filter(Boolean)
        .join(' – ');
    const hasDetails = product.specifications.length > 0 || Boolean(variant?.sku);

    const add = () => {
        if (!variant) return;
        router.post(
            to('/cart/items'),
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

            <div className="container-page grid gap-10 pt-6 pb-16 lg:grid-cols-12 lg:gap-x-14">
                <div className="lg:col-span-6 xl:col-span-7">
                    <div className="lg:sticky lg:top-28">
                        <ProductGallery
                            images={product.images}
                            tint="var(--color-mist)"
                            label={t(':name images', { name: product.name })}
                            placeholder={
                                <ProductPlaceholder
                                    tint={product.category.tint}
                                    motif={MOTIF_BY_CATEGORY[product.category.slug]}
                                />
                            }
                        />
                    </div>
                </div>

                <div className="lg:col-span-6 xl:col-span-5">
                    {/* Purchase panel */}
                    <section aria-labelledby="product-title">
                        <Link
                            href={to(`/shop/${product.category.slug}`)}
                            className="text-xs font-semibold tracking-[0.04em] text-dune-deep uppercase underline underline-offset-4 hover:text-aloe"
                        >
                            {product.category.name}
                        </Link>
                        <h1
                            id="product-title"
                            className="mt-3 text-[1.75rem] leading-tight font-medium text-ink sm:text-[2rem]"
                        >
                            {product.name}
                        </h1>
                        <p className="mt-5 flex flex-wrap items-center gap-3 text-2xl font-semibold">
                            {variant ? (
                                <Price amount={variant.price_minor} regular={variant.regular_price_minor} />
                            ) : null}
                            {product.is_bestseller && (
                                <span className="rounded-full bg-sand px-3 py-1 text-[0.7rem] font-semibold tracking-[0.04em] text-stone uppercase">
                                    {t('Bestseller')}
                                </span>
                            )}
                        </p>
                        {reference && <p className="mt-2 text-sm text-stone">{reference}</p>}
                        {product.summary && (
                            <p className="mt-5 text-[1.05rem] leading-relaxed text-stone">{product.summary}</p>
                        )}

                        {hasOptions && (
                            <fieldset className="mt-8">
                                <legend className="mb-3 text-sm font-medium">
                                    {t(':label:', { label: product.option_label ?? t('Option') })}{' '}
                                    <span className="font-normal text-stone">{variant?.name}</span>
                                </legend>
                                <div className="flex flex-wrap gap-2">
                                    {product.variants.map((v) => (
                                        <label
                                            key={v.id}
                                            className={cn(
                                                'relative inline-flex h-11 cursor-pointer items-center rounded-full border px-5 text-[0.95rem] transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-aloe',
                                                v.id === variant?.id
                                                    ? 'border-dune-deep bg-dune-deep text-white'
                                                    : 'border-line bg-white hover:border-ink',
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
                                            {!v.in_stock && <span className="sr-only"> {t('(out of stock)')}</span>}
                                        </label>
                                    ))}
                                </div>
                            </fieldset>
                        )}

                        <div className="mt-8">
                            {variant?.in_stock ? (
                                <>
                                    <div className="flex flex-wrap items-center gap-3">
                                        <QuantityStepper
                                            label={t('Quantity')}
                                            value={quantity}
                                            max={variant.max_quantity}
                                            onChange={setQuantity}
                                        />
                                        <Button
                                            size="lg"
                                            wrap
                                            className="min-w-52 flex-1"
                                            loading={adding}
                                            onClick={add}
                                            disabled={!orderingEnabled}
                                        >
                                            {t('Add to cart')}
                                        </Button>
                                    </div>
                                    {variant.low_stock && (
                                        <p className="mt-3 text-sm text-dune-deep">{t('Only a few left.')}</p>
                                    )}
                                    {errors.quantity && (
                                        <p className="mt-3 text-sm text-clay" role="alert">
                                            {errors.quantity}
                                        </p>
                                    )}
                                    {!orderingEnabled && (
                                        <p className="mt-3 text-sm text-stone">
                                            {t('Online ordering is paused at the moment.')}
                                        </p>
                                    )}
                                </>
                            ) : (
                                <p className="rounded-2xl bg-sand px-5 py-4 text-stone">
                                    {hasOptions
                                        ? t('This option is out of stock.')
                                        : t('This product is out of stock.')}{' '}
                                    {t('Please check back soon or')}{' '}
                                    <Link href={to('/contact')} className="underline underline-offset-4">
                                        {t('contact us')}
                                    </Link>
                                    .
                                </p>
                            )}
                            <Button
                                asChild
                                size="lg"
                                variant="secondary"
                                wrap
                                className="mt-3 w-full border-stone text-stone"
                            >
                                <Link href={to(`/shop/${product.category.slug}`)}>{t('View similar products')}</Link>
                            </Button>
                        </div>

                        <ul className="mt-8 grid gap-5 rounded-2xl bg-panel p-6 sm:grid-cols-2">
                            <li className="flex gap-4 sm:flex-col sm:items-center sm:text-center">
                                <span className="flex size-12 shrink-0 items-center justify-center rounded-full bg-white text-dune-deep">
                                    <Banknote className="size-5" strokeWidth={1.5} aria-hidden="true" />
                                </span>
                                <span>
                                    <span className="block font-semibold">{t('Cash on delivery')}</span>
                                    <span className="mt-1 block text-sm text-stone">{delivery.cod_instructions}</span>
                                </span>
                            </li>
                            <li className="flex gap-4 sm:flex-col sm:items-center sm:text-center">
                                <span className="flex size-12 shrink-0 items-center justify-center rounded-full bg-white text-dune-deep">
                                    <Truck className="size-5" strokeWidth={1.5} aria-hidden="true" />
                                </span>
                                <span>
                                    <span className="block font-semibold">{t('Delivery')}</span>
                                    <span className="mt-1 block text-sm text-stone">
                                        {delivery.min_fee_minor !== null
                                            ? t('From :amount.', { amount: money(delivery.min_fee_minor) })
                                            : delivery.info}
                                    </span>
                                </span>
                            </li>
                        </ul>
                    </section>

                    {/* Product information */}
                    <section aria-label={t('Product information')} className="mt-10 border-t border-line">
                        {product.sections.map((section, i) => (
                            <InfoSection key={section.id} title={section.title} open={i === 0}>
                                <SafeHtml html={section.html} className="prose-compact text-stone" />
                            </InfoSection>
                        ))}
                        {hasDetails && (
                            <InfoSection title={t('Details')} open={product.sections.length === 0}>
                                <dl className="divide-y divide-line border-y border-line">
                                    {product.specifications.map((s) => (
                                        <div key={s.label} className="grid grid-cols-3 gap-4 py-3 text-[0.95rem]">
                                            <dt className="text-stone">{s.label}</dt>
                                            <dd className="col-span-2">{s.value}</dd>
                                        </div>
                                    ))}
                                    {variant?.sku && (
                                        <div className="grid grid-cols-3 gap-4 py-3 text-[0.95rem]">
                                            <dt className="text-stone">{t('Reference')}</dt>
                                            <dd className="col-span-2">{variant.sku}</dd>
                                        </div>
                                    )}
                                </dl>
                            </InfoSection>
                        )}
                        {delivery.zones.length > 0 && (
                            <InfoSection title={t('Delivery areas')} open={false}>
                                <ul className="space-y-2 text-[0.95rem] text-stone">
                                    {delivery.zones.map((z) => (
                                        <li key={z.name} className="flex justify-between gap-4">
                                            <span>
                                                {z.name}
                                                {z.estimate && <span className="text-stone/80"> · {z.estimate}</span>}
                                            </span>
                                            <span className="text-right tabular-nums">
                                                {money(z.fee_minor)}
                                                {z.free_over_minor !== null && (
                                                    <span className="block text-xs">
                                                        {t('free over :amount', { amount: money(z.free_over_minor) })}
                                                    </span>
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                                {delivery.info && <p className="mt-4 text-[0.95rem] text-stone">{delivery.info}</p>}
                            </InfoSection>
                        )}
                    </section>
                </div>
            </div>

            {related.length > 0 && (
                <ProductRail
                    id="related-heading"
                    title={t('More from :category', { category: product.category.name })}
                    products={related}
                    link={{ href: to(`/shop/${product.category.slug}`), label: t('See all') }}
                    className="border-t border-line"
                />
            )}
        </>
    );
}

/** One collapsible block of product information (native <details>, so it works before the page script loads). */
function InfoSection({ title, open, children }: { title: string; open: boolean; children: ReactNode }) {
    return (
        <details className="group border-b border-line" open={open}>
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 py-5 [&::-webkit-details-marker]:hidden">
                <h2 className="text-lg font-medium text-ink">{title}</h2>
                <Plus className="size-5 shrink-0 text-stone group-open:hidden" strokeWidth={1.5} aria-hidden="true" />
                <Minus
                    className="hidden size-5 shrink-0 text-stone group-open:block"
                    strokeWidth={1.5}
                    aria-hidden="true"
                />
            </summary>
            <div className="pb-7">{children}</div>
        </details>
    );
}
