import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface PriceProps {
    amount: number | null;
    regular?: number | null;
    from?: boolean;
    currency?: string;
    className?: string;
}

/** Price with optional struck-through regular price, read out clearly by screen readers. */
export function Price({ amount, regular, from = false, currency, className }: PriceProps) {
    const shared = useShared();
    const { t, money } = useI18n();
    const code = currency ?? shared.store.currency;

    if (amount === null) {
        return <span className={cn('text-stone', className)}>{t('Price on request')}</span>;
    }

    const onSale = regular !== undefined && regular !== null && regular > amount;

    return (
        <span className={cn('inline-flex flex-wrap items-baseline gap-x-2 tabular-nums', className)}>
            {from && <span className="text-[0.85em] text-stone">{t('From')}</span>}
            {onSale ? (
                <>
                    <span className="sr-only">{t('Sale price')}</span>
                    <span className="text-dune-deep">{money(amount, code)}</span>
                    <span className="sr-only">{t(', was')}</span>
                    <s className="text-[0.85em] text-stone decoration-1">{money(regular, code)}</s>
                </>
            ) : (
                <span>{money(amount, code)}</span>
            )}
        </span>
    );
}
