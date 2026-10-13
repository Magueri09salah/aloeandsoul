import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

import type { CartLine } from '@/types';

import { Price } from '@/components/ui/price';
import { QuantityStepper } from '@/components/ui/quantity-stepper';
import { ResponsiveImage } from '@/components/ui/responsive-image';
import { ProductPlaceholder } from '@/components/store/product-placeholder';
import { useShared } from '@/hooks/use-shared';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';

/** Cart lines with quantity controls; used by the drawer and the cart page. */
export function CartLines({ lines, compact = false }: { lines: CartLine[]; compact?: boolean }) {
    const { store, errors } = useShared();
    const [busy, setBusy] = useState<number | null>(null);

    const update = (line: CartLine, quantity: number) => {
        const options = {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setBusy(line.id),
            onFinish: () => setBusy(null),
        };
        if (quantity < 1) {
            router.delete(`/cart/items/${line.id}`, options);
        } else {
            router.patch(`/cart/items/${line.id}`, { quantity }, options);
        }
    };

    return (
        <ul className="divide-y divide-line">
            {lines.map((line) => (
                <li key={line.id} className={cn('flex gap-4 py-5', busy === line.id && 'opacity-60')} aria-busy={busy === line.id || undefined}>
                    <Link
                        href={`/products/${line.product_slug}`}
                        className={cn('shrink-0 overflow-hidden', compact ? 'h-24 w-20' : 'h-32 w-26')}
                        style={{ backgroundColor: line.tint }}
                        tabIndex={-1}
                        aria-hidden="true"
                    >
                        {line.image ? <ResponsiveImage image={line.image} sizes="104px" alt="" /> : <ProductPlaceholder tint={line.tint} />}
                    </Link>
                    <div className="flex min-w-0 flex-1 flex-col">
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <Link href={`/products/${line.product_slug}`} className="font-medium hover:text-aloe">
                                    {line.product_name}
                                </Link>
                                {line.variant_name && (
                                    <p className="text-sm text-stone">
                                        {line.option_label ? `${line.option_label}: ` : ''}
                                        {line.variant_name}
                                    </p>
                                )}
                                {line.unit_price_minor !== null && (
                                    <p className="mt-0.5 text-sm text-stone">
                                        <Price amount={line.unit_price_minor} regular={line.regular_price_minor} /> each
                                    </p>
                                )}
                            </div>
                            <p className="shrink-0 tabular-nums">{line.issue ? '—' : formatMoney(line.line_total_minor, store.currency)}</p>
                        </div>
                        {line.issue && (
                            <p className="mt-2 text-sm text-clay" role="status">
                                {line.issue}
                            </p>
                        )}
                        <div className="mt-auto flex items-center justify-between gap-3 pt-3">
                            {line.max_quantity > 0 ? (
                                <QuantityStepper
                                    size="sm"
                                    label={`Quantity of ${line.product_name}`}
                                    value={line.quantity}
                                    min={1}
                                    max={line.max_quantity}
                                    disabled={busy === line.id}
                                    onChange={(q) => update(line, q)}
                                />
                            ) : (
                                <span />
                            )}
                            <button
                                type="button"
                                onClick={() => update(line, 0)}
                                disabled={busy === line.id}
                                className="text-sm text-stone underline underline-offset-4 hover:text-clay"
                            >
                                Remove<span className="sr-only"> {line.product_name}</span>
                            </button>
                        </div>
                    </div>
                </li>
            ))}
            {errors.quantity && (
                <li className="py-3 text-sm text-clay" role="alert">
                    {errors.quantity}
                </li>
            )}
        </ul>
    );
}
