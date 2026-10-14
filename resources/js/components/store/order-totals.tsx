import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface OrderTotalsProps {
    currency: string;
    subtotal: number;
    discount: number;
    shipping: number | null;
    total: number | null;
    couponCode?: string | null;
    className?: string;
}

/** Subtotal, discount, delivery and the amount payable in cash on delivery. */
export function OrderTotals({ currency, subtotal, discount, shipping, total, couponCode, className }: OrderTotalsProps) {
    const { t, money } = useI18n();

    return (
        <dl className={cn('space-y-2.5 text-[0.95rem]', className)}>
            <div className="flex justify-between gap-4">
                <dt className="text-stone">{t('Subtotal')}</dt>
                <dd className="tabular-nums">{money(subtotal, currency)}</dd>
            </div>
            {discount > 0 && (
                <div className="flex justify-between gap-4">
                    <dt className="text-stone">{couponCode ? t('Discount (:code)', { code: couponCode }) : t('Discount')}</dt>
                    <dd className="text-dune-deep tabular-nums">−{money(discount, currency)}</dd>
                </div>
            )}
            <div className="flex justify-between gap-4">
                <dt className="text-stone">{t('Delivery')}</dt>
                <dd className="tabular-nums">{shipping === null ? t('Choose a delivery area') : shipping === 0 ? t('Free') : money(shipping, currency)}</dd>
            </div>
            <div className="flex items-baseline justify-between gap-4 border-t border-line pt-4">
                <dt>
                    <span className="block font-medium">{t('To pay on delivery')}</span>
                    <span className="text-sm text-stone">{t('In cash, to the courier')}</span>
                </dt>
                <dd className="font-display text-3xl tabular-nums">{total === null ? '—' : money(total, currency)}</dd>
            </div>
        </dl>
    );
}
