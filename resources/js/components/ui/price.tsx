import { useShared } from '@/hooks/use-shared';
import { formatMoney } from '@/lib/money';
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
    const code = currency ?? shared.store.currency;

    if (amount === null) {
        return <span className={cn('text-stone', className)}>Price on request</span>;
    }

    const onSale = regular !== undefined && regular !== null && regular > amount;

    return (
        <span className={cn('inline-flex flex-wrap items-baseline gap-x-2 tabular-nums', className)}>
            {from && <span className="text-[0.85em] text-stone">From</span>}
            {onSale ? (
                <>
                    <span className="sr-only">Sale price</span>
                    <span className="text-dune-deep">{formatMoney(amount, code)}</span>
                    <span className="sr-only">, was</span>
                    <s className="text-[0.85em] text-stone decoration-1">{formatMoney(regular, code)}</s>
                </>
            ) : (
                <span>{formatMoney(amount, code)}</span>
            )}
        </span>
    );
}
