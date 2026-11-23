import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

import type { ProductCard as ProductCardData } from '@/types';

import { Button } from '@/components/ui/button';
import { Price } from '@/components/ui/price';
import { ResponsiveImage } from '@/components/ui/responsive-image';
import { useCartUi } from '@/components/store/cart-context';
import { ProductPlaceholder } from '@/components/store/product-placeholder';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const MOTIF_BY_CATEGORY: Record<string, string> = {
    'sport-fitness': 'horizon',
    beauty: 'circle',
    hygiene: 'wave',
    'nutrition-supplements': 'leaf',
};

interface ProductCardProps {
    product: ProductCardData;
    sizes?: string;
    priority?: boolean;
    className?: string;
}

/** Product tile: rounded photo frame with labels, category, name, price and a full-width action. */
export function ProductCard({
    product,
    sizes = '(min-width: 1024px) 25vw, (min-width: 640px) 33vw, 50vw',
    priority = false,
    className,
}: ProductCardProps) {
    const cart = useCartUi();
    const [adding, setAdding] = useState(false);
    const { t, tc, to } = useI18n();
    const href = to(`/products/${product.slug}`);

    const quickAdd = () => {
        if (!product.quick_add_variant_id) return;
        router.post(
            to('/cart/items'),
            { variant_id: product.quick_add_variant_id, quantity: 1 },
            {
                preserveScroll: true,
                onStart: () => setAdding(true),
                onFinish: () => setAdding(false),
                onSuccess: (page) => {
                    if (!(page.props.errors as Record<string, string>).quantity) cart.open();
                },
            },
        );
    };

    // "Out of stock" is said by the action button, not repeated on the photo.
    const badges = [
        { show: product.in_stock && product.on_sale, label: t('Sale'), strong: true },
        { show: product.is_bestseller, label: t('Bestseller'), strong: false },
    ].filter((b) => b.show);

    return (
        <article className={cn('group relative flex flex-col', className)}>
            {/* The image repeats the name link, so it is hidden from keyboard and screen readers. */}
            <Link href={href} className="block" prefetch="hover" tabIndex={-1} aria-hidden="true">
                <div className="relative aspect-square overflow-hidden rounded-2xl bg-mist">
                    {product.image ? (
                        <ResponsiveImage
                            image={product.image}
                            sizes={sizes}
                            priority={priority}
                            alt=""
                            className="object-contain p-3 transition-transform duration-700 ease-(--ease-out-soft) motion-safe:group-hover:scale-[1.04]"
                        />
                    ) : (
                        <ProductPlaceholder
                            tint={product.category.tint}
                            motif={MOTIF_BY_CATEGORY[product.category.slug]}
                        />
                    )}
                </div>
            </Link>
            {badges.length > 0 && (
                <ul className="pointer-events-none absolute top-2 left-2 flex flex-col items-start gap-1.5 sm:top-3 sm:left-3">
                    {badges.map((badge) => (
                        <li
                            key={badge.label}
                            className={cn(
                                'rounded-full px-2 py-1 text-[0.65rem] font-semibold tracking-[0.02em] uppercase sm:px-3 sm:text-[0.7rem] sm:tracking-[0.04em]',
                                badge.strong ? 'bg-dune-deep text-white' : 'bg-white/90 text-stone',
                            )}
                        >
                            {badge.label}
                        </li>
                    ))}
                </ul>
            )}

            <Link
                href={to(`/shop/${product.category.slug}`)}
                className="mt-4 self-start text-xs font-semibold tracking-[0.04em] text-dune-deep uppercase underline underline-offset-4 hover:text-aloe"
            >
                {product.category.name}
            </Link>
            <h3 className="mt-1.5 text-[0.98rem] leading-snug text-ink">
                <Link href={href} prefetch="hover" className="block hover:text-dune-deep">
                    {product.name}
                </Link>
            </h3>
            <div className="mt-3 flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                <Price
                    amount={product.price_minor}
                    regular={product.regular_price_minor}
                    from={product.price_varies}
                    className="text-[0.95rem] text-stone"
                />
                {product.variant_count > 1 && product.in_stock && (
                    <span className="text-sm text-stone">
                        {/* "2 flavours" / "2 saveurs": the option name takes a plain -s in both languages. */}
                        {product.option_label
                            ? `${product.variant_count} ${product.option_label.toLowerCase()}s`
                            : tc(':count option|:count options', product.variant_count)}
                    </span>
                )}
            </div>

            <div className="mt-auto pt-4">
                {product.quick_add_variant_id ? (
                    <Button
                        variant="secondary"
                        wrap
                        className="min-h-12 w-full border-stone px-2 text-stone max-sm:text-[0.78rem] max-sm:tracking-[0.01em] sm:px-6"
                        onClick={quickAdd}
                        loading={adding}
                        aria-label={t('Add :name to cart', { name: product.name })}
                    >
                        {t('Add to cart')}
                    </Button>
                ) : product.in_stock ? (
                    <Button
                        asChild
                        variant="secondary"
                        wrap
                        className="min-h-12 w-full border-stone px-2 text-stone max-sm:text-[0.78rem] max-sm:tracking-[0.01em] sm:px-6"
                    >
                        <Link href={href} aria-label={t('Choose an option for :name', { name: product.name })}>
                            {t('Choose an option')}
                        </Link>
                    </Button>
                ) : (
                    <p className="flex min-h-12 w-full items-center justify-center rounded-full border border-line px-2 py-2 text-center text-[0.78rem] leading-tight font-semibold tracking-[0.03em] text-balance text-stone/80 uppercase max-sm:tracking-[0.01em] sm:text-[0.95rem]">
                        {t('Out of stock')}
                    </p>
                )}
            </div>
        </article>
    );
}

export { MOTIF_BY_CATEGORY };
