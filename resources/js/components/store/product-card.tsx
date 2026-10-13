import { Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

import type { ProductCard as ProductCardData } from '@/types';

import { Spinner } from '@/components/ui/spinner';
import { Price } from '@/components/ui/price';
import { ResponsiveImage } from '@/components/ui/responsive-image';
import { useCartUi } from '@/components/store/cart-context';
import { ProductPlaceholder } from '@/components/store/product-placeholder';
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

export function ProductCard({
    product,
    sizes = '(min-width: 1024px) 25vw, (min-width: 640px) 33vw, 50vw',
    priority = false,
    className,
}: ProductCardProps) {
    const cart = useCartUi();
    const [adding, setAdding] = useState(false);
    const href = `/products/${product.slug}`;

    const quickAdd = () => {
        if (!product.quick_add_variant_id) return;
        router.post(
            '/cart/items',
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

    return (
        <article className={cn('group relative flex flex-col', className)}>
            <Link href={href} className="block focus-visible:outline-offset-4" prefetch="hover">
                <div className="relative aspect-[4/5] overflow-hidden" style={{ backgroundColor: product.category.tint }}>
                    {product.image ? (
                        <ResponsiveImage
                            image={product.image}
                            sizes={sizes}
                            priority={priority}
                            alt=""
                            className="transition-transform duration-700 ease-(--ease-out-soft) motion-safe:group-hover:scale-[1.03]"
                        />
                    ) : (
                        <ProductPlaceholder tint={product.category.tint} motif={MOTIF_BY_CATEGORY[product.category.slug]} />
                    )}
                    <div className="absolute top-3 left-3 flex flex-col items-start gap-1.5">
                        {!product.in_stock && <span className="rounded-full bg-white/90 px-2.5 py-1 text-xs font-medium text-stone">Out of stock</span>}
                        {product.in_stock && product.on_sale && <span className="rounded-full bg-ink px-2.5 py-1 text-xs font-medium text-ivory">Sale</span>}
                        {product.is_bestseller && <span className="rounded-full bg-white/90 px-2.5 py-1 text-xs font-medium text-ink">Bestseller</span>}
                    </div>
                </div>
                <h3 className="mt-3.5 text-[0.98rem] leading-snug font-medium text-ink group-hover:text-aloe">{product.name}</h3>
            </Link>
            <div className="mt-1 flex min-h-9 items-center justify-between gap-3">
                <Price amount={product.price_minor} regular={product.regular_price_minor} from={product.price_varies} className="text-[0.95rem]" />
                {product.quick_add_variant_id ? (
                    <button
                        type="button"
                        onClick={quickAdd}
                        disabled={adding}
                        className="inline-flex size-9 shrink-0 items-center justify-center rounded-full border border-line bg-white text-ink transition-colors hover:border-ink hover:bg-ink hover:text-ivory disabled:opacity-60"
                        aria-label={`Add ${product.name} to cart`}
                    >
                        {adding ? <Spinner /> : <Plus className="size-4" aria-hidden="true" />}
                    </button>
                ) : (
                    product.variant_count > 1 &&
                    product.in_stock && (
                        <span className="text-sm text-stone">
                            {product.variant_count} {product.option_label ? `${product.option_label.toLowerCase()}s` : 'options'}
                        </span>
                    )
                )}
            </div>
        </article>
    );
}

export { MOTIF_BY_CATEGORY };
