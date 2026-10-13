import { Link, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Circle } from 'lucide-react';

import { DailyChart } from '@/components/admin/daily-chart';
import { EmptyState, OrderStatusBadge, PageHeader, Panel, Stat, Table, td, th } from '@/components/admin/ui';
import { useShared } from '@/hooks/use-shared';
import { formatDateTime } from '@/lib/date';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';

interface DashboardProps {
    range: string;
    currency: string;
    metrics: {
        orders_today: number;
        awaiting_confirmation: number;
        awaiting_fulfilment: number;
        out_for_delivery: number;
        orders_in_range: number;
        delivered: number;
        cancelled: number;
        order_value_minor: number;
        collected_minor: number;
        awaiting_collection_minor: number;
    };
    series: { date: string; orders: number; value_minor: number }[];
    bestsellers: { product_id: number; name: string; units: number; value_minor: number }[];
    lowStock: { id: number; product_id: number; name: string; stock: number }[];
    recentOrders: { reference: string; customer_name: string; city: string; total_minor: number; currency: string; status: string; status_label: string; created_at: string; risk: boolean }[];
    checklist: { label: string; done: boolean; href: string | null }[];
}

const RANGES = [
    { value: 'today', label: 'Today' },
    { value: '7d', label: '7 days' },
    { value: '30d', label: '30 days' },
    { value: '90d', label: '90 days' },
];

export default function Dashboard({ range, currency, metrics, series, bestsellers, lowStock, recentOrders, checklist }: DashboardProps) {
    const { store, auth } = useShared();
    const money = (v: number) => formatMoney(v, currency);
    const pendingChecklist = checklist.filter((c) => !c.done);

    return (
        <>
            <PageHeader title={`Hello, ${auth?.user?.name.split(' ')[0] ?? ''}`} description="What needs your attention, and how the shop is doing." />

            {pendingChecklist.length > 0 && (
                <Panel title="Before you launch" description={`${checklist.length - pendingChecklist.length} of ${checklist.length} done`} className="mb-6">
                    <ul className="grid gap-2 sm:grid-cols-2">
                        {checklist.map((item) => (
                            <li key={item.label} className="flex items-start gap-2.5 text-sm">
                                {item.done ? (
                                    <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-aloe" aria-label="Done" />
                                ) : (
                                    <Circle className="mt-0.5 size-4 shrink-0 text-stone" aria-label="To do" />
                                )}
                                {item.href && !item.done ? (
                                    <Link href={item.href} className="underline underline-offset-4 hover:text-aloe">
                                        {item.label}
                                    </Link>
                                ) : (
                                    <span className={cn(item.done && 'text-stone line-through')}>{item.label}</span>
                                )}
                            </li>
                        ))}
                    </ul>
                </Panel>
            )}

            <h2 className="mb-3 text-sm font-medium text-stone">Needs action now</h2>
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <Stat label="Orders today" value={metrics.orders_today} />
                <Stat label="Awaiting confirmation" value={metrics.awaiting_confirmation} href="/admin/orders?status=PENDING_CONFIRMATION" />
                <Stat label="To prepare and send" value={metrics.awaiting_fulfilment} href="/admin/orders?status=to_fulfil" />
                <Stat label="Out for delivery" value={metrics.out_for_delivery} href="/admin/orders?status=SHIPPED" />
            </div>

            <div className="mt-10 mb-3 flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-sm font-medium text-stone">Performance</h2>
                <div role="group" aria-label="Date range" className="inline-flex rounded-full border border-line bg-white p-0.5">
                    {RANGES.map((r) => (
                        <button
                            key={r.value}
                            type="button"
                            onClick={() => router.get('/admin', { range: r.value }, { preserveScroll: true, preserveState: true })}
                            aria-pressed={range === r.value}
                            className={cn('rounded-full px-3.5 py-1.5 text-sm', range === r.value ? 'bg-ink text-ivory' : 'hover:bg-panel')}
                        >
                            {r.label}
                        </button>
                    ))}
                </div>
            </div>
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <Stat label="Cash collected" value={money(metrics.collected_minor)} hint="Delivered orders with payment recorded. This is your revenue." />
                <Stat label="Order value" value={money(metrics.order_value_minor)} hint={`${metrics.orders_in_range} orders placed, excluding cancelled. Not yet revenue.`} />
                <Stat label="Delivered" value={metrics.delivered} hint={`${metrics.cancelled} cancelled in the same period`} />
                <Stat
                    label="Delivered, cash not recorded"
                    value={money(metrics.awaiting_collection_minor)}
                    hint="Record collected cash on each delivered order."
                    href="/admin/orders?status=DELIVERED&payment=pending"
                />
            </div>

            <Panel title="Order value per day" description="Orders placed, excluding cancelled" className="mt-6">
                <DailyChart series={series} currency={currency} />
            </Panel>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Panel title="Bestsellers" description="Units ordered in this period" padded={false}>
                    {bestsellers.length === 0 ? (
                        <EmptyState title="No orders in this period yet." />
                    ) : (
                        <Table>
                            <thead>
                                <tr>
                                    <th className={th}>Product</th>
                                    <th className={cn(th, 'text-right')}>Units</th>
                                    <th className={cn(th, 'text-right')}>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                {bestsellers.map((b) => (
                                    <tr key={b.product_id}>
                                        <td className={td}>{b.name}</td>
                                        <td className={cn(td, 'text-right tabular-nums')}>{b.units}</td>
                                        <td className={cn(td, 'text-right tabular-nums')}>{money(b.value_minor)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                    )}
                </Panel>

                <Panel title="Low stock" description="Published products at or below their alert level" padded={false} actions={<Link href="/admin/inventory?level=low" className="text-sm underline underline-offset-4">All stock</Link>}>
                    {lowStock.length === 0 ? (
                        <EmptyState title="Stock levels look fine." />
                    ) : (
                        <ul className="divide-y divide-line">
                            {lowStock.map((v) => (
                                <li key={v.id} className="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                    <Link href={`/admin/products/${v.product_id}/edit`} className="hover:underline">
                                        {v.name}
                                    </Link>
                                    <span className={cn('inline-flex items-center gap-1.5 tabular-nums', v.stock === 0 ? 'text-clay' : 'text-dune-deep')}>
                                        {v.stock === 0 && <AlertTriangle className="size-3.5" aria-hidden="true" />}
                                        {v.stock === 0 ? 'Out of stock' : `${v.stock} left`}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>
            </div>

            <Panel title="Latest orders" padded={false} className="mt-6" actions={<Link href="/admin/orders" className="text-sm underline underline-offset-4">All orders</Link>}>
                {recentOrders.length === 0 ? (
                    <EmptyState title="No orders yet.">Orders placed on the shop will appear here.</EmptyState>
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>Order</th>
                                <th className={th}>Customer</th>
                                <th className={th}>Status</th>
                                <th className={cn(th, 'text-right')}>Total</th>
                                <th className={th}>Placed</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentOrders.map((o) => (
                                <tr key={o.reference} className="hover:bg-panel/50">
                                    <td className={td}>
                                        <Link href={`/admin/orders/${o.reference}`} className="font-medium underline-offset-4 hover:underline">
                                            {o.reference}
                                        </Link>
                                        {o.risk && <AlertTriangle className="ml-1.5 inline size-3.5 text-dune-deep" aria-label="Flagged for review" />}
                                    </td>
                                    <td className={td}>
                                        {o.customer_name}
                                        <span className="block text-xs text-stone">{o.city}</span>
                                    </td>
                                    <td className={td}>
                                        <OrderStatusBadge status={o.status} label={o.status_label} />
                                    </td>
                                    <td className={cn(td, 'text-right tabular-nums')}>{formatMoney(o.total_minor, o.currency)}</td>
                                    <td className={cn(td, 'whitespace-nowrap text-stone')}>{formatDateTime(o.created_at, store.timezone)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                )}
            </Panel>
        </>
    );
}
