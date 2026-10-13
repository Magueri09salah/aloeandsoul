import { Link } from '@inertiajs/react';

import type { Paginated } from '@/types';

import { EmptyState, PageHeader, Pagination, Panel, Table, td, th } from '@/components/admin/ui';
import { useShared } from '@/hooks/use-shared';
import { formatDateTime } from '@/lib/date';
import { cn } from '@/lib/utils';

interface Movement {
    id: number;
    change: number;
    stock_after: number;
    reason: string;
    order_reference: string | null;
    user: string | null;
    note: string | null;
    created_at: string;
}

interface Props {
    variant: { id: number; name: string; sku: string | null; stock: number; product_id: number };
    movements: Paginated<Movement>;
}

export default function InventoryHistory({ variant, movements }: Props) {
    const { store } = useShared();

    return (
        <>
            <PageHeader back={{ href: '/admin/inventory', label: 'Stock' }} title={variant.name} description={`${variant.sku ? `SKU ${variant.sku}. ` : ''}${variant.stock} in stock now.`} />
            <Panel padded={false}>
                {movements.data.length === 0 ? (
                    <EmptyState title="No stock changes yet." />
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>When</th>
                                <th className={th}>Reason</th>
                                <th className={cn(th, 'text-right')}>Change</th>
                                <th className={cn(th, 'text-right')}>After</th>
                                <th className={th}>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            {movements.data.map((m) => (
                                <tr key={m.id}>
                                    <td className={cn(td, 'whitespace-nowrap text-stone')}>{formatDateTime(m.created_at, store.timezone)}</td>
                                    <td className={td}>
                                        {m.reason}
                                        {m.order_reference && (
                                            <Link href={`/admin/orders/${m.order_reference}`} className="ml-1 underline underline-offset-4">
                                                {m.order_reference}
                                            </Link>
                                        )}
                                        {m.note && <span className="block text-xs text-stone">{m.note}</span>}
                                    </td>
                                    <td className={cn(td, 'text-right tabular-nums', m.change < 0 ? 'text-clay' : 'text-aloe')}>
                                        {m.change > 0 ? `+${m.change}` : m.change}
                                    </td>
                                    <td className={cn(td, 'text-right tabular-nums')}>{m.stock_after}</td>
                                    <td className={cn(td, 'text-stone')}>{m.user ?? (m.order_reference ? 'Customer order' : '—')}</td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                )}
                <Pagination page={movements} />
            </Panel>
        </>
    );
}
