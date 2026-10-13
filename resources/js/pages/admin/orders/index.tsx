import { Link, router } from '@inertiajs/react';
import { AlertTriangle, Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import type { Option, Paginated } from '@/types';

import { EmptyState, OrderStatusBadge, PageHeader, Pagination, PaymentBadge, Panel, Table, td, th } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input, Label, Select } from '@/components/ui/input';
import { useShared } from '@/hooks/use-shared';
import { formatDateTime } from '@/lib/date';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';

interface OrderRow {
    reference: string;
    customer_name: string;
    customer_phone: string;
    city: string;
    total_minor: number;
    currency: string;
    status: string;
    status_label: string;
    payment_status: string;
    payment_status_label: string;
    created_at: string;
    risk: boolean;
}

interface Filters {
    q: string;
    status: string | null;
    payment: string | null;
    from: string | null;
    to: string | null;
    flagged: boolean;
}

interface Props {
    orders: Paginated<OrderRow>;
    filters: Filters;
    statuses: Option[];
    paymentStatuses: Option[];
    counts: Record<string, number>;
}

export default function OrdersIndex({ orders, filters, statuses, paymentStatuses, counts }: Props) {
    const { store } = useShared();
    const [draft, setDraft] = useState(filters);

    const apply = (next: Partial<Filters>) => {
        const merged = { ...draft, ...next };
        setDraft(merged);
        const query = Object.fromEntries(
            Object.entries({ ...merged, flagged: merged.flagged ? '1' : '' }).filter(([, v]) => v !== null && v !== ''),
        );
        router.get('/admin/orders', query, { preserveState: true, preserveScroll: true, replace: true });
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        apply({});
    };

    return (
        <>
            <PageHeader title="Orders" description="Every order is paid in cash on delivery. Confirm new orders, then move them through preparation and delivery." />

            <div className="mb-4 flex gap-2 overflow-x-auto pb-1" role="group" aria-label="Filter by status">
                <StatusChip active={!filters.status} onClick={() => apply({ status: null })} label="All" />
                {statuses.map((s) => (
                    <StatusChip key={s.value} active={filters.status === s.value} onClick={() => apply({ status: s.value })} label={s.label} count={counts[s.value]} />
                ))}
            </div>

            <Panel padded={false}>
                <form onSubmit={submit} className="grid gap-3 border-b border-line p-4 sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto_auto_auto]">
                    <div className="relative">
                        <label htmlFor="q" className="sr-only">
                            Search orders
                        </label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone" aria-hidden="true" />
                        <Input id="q" type="search" value={draft.q} onChange={(e) => setDraft({ ...draft, q: e.target.value })} placeholder="Reference, name or phone" className="h-10 pl-9" />
                    </div>
                    <div>
                        <label htmlFor="payment" className="sr-only">
                            Payment
                        </label>
                        <Select id="payment" value={draft.payment ?? ''} onChange={(e) => apply({ payment: e.target.value || null })} className="h-10">
                            <option value="">Any payment status</option>
                            {paymentStatuses.map((p) => (
                                <option key={p.value} value={p.value}>
                                    {p.label}
                                </option>
                            ))}
                        </Select>
                    </div>
                    <div className="flex items-center gap-2">
                        <Label htmlFor="from" className="text-stone">
                            From
                        </Label>
                        <Input id="from" type="date" value={draft.from ?? ''} onChange={(e) => apply({ from: e.target.value || null })} className="h-10" />
                    </div>
                    <div className="flex items-center gap-2">
                        <Label htmlFor="to" className="text-stone">
                            To
                        </Label>
                        <Input id="to" type="date" value={draft.to ?? ''} onChange={(e) => apply({ to: e.target.value || null })} className="h-10" />
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox id="flagged" checked={draft.flagged} onCheckedChange={(v) => apply({ flagged: v === true })} />
                        <Label htmlFor="flagged" className="font-normal whitespace-nowrap">
                            Flagged only
                        </Label>
                    </div>
                    <Button type="submit" size="sm" variant="secondary" className="h-10">
                        Search
                    </Button>
                </form>

                {orders.data.length === 0 ? (
                    <EmptyState title="No orders found.">{filters.q || filters.status ? 'Try a different search or filter.' : 'Orders placed on the shop will appear here.'}</EmptyState>
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>Order</th>
                                <th className={th}>Customer</th>
                                <th className={th}>Status</th>
                                <th className={th}>Payment</th>
                                <th className={cn(th, 'text-right')}>To collect</th>
                                <th className={th}>Placed</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data.map((o) => (
                                <tr key={o.reference} className="hover:bg-panel/50">
                                    <td className={td}>
                                        <Link href={`/admin/orders/${o.reference}`} className="font-medium whitespace-nowrap underline-offset-4 hover:underline">
                                            {o.reference}
                                        </Link>
                                        {o.risk && <AlertTriangle className="ml-1.5 inline size-3.5 text-dune-deep" aria-label="Flagged for review" />}
                                    </td>
                                    <td className={td}>
                                        {o.customer_name}
                                        <span className="block text-xs text-stone">
                                            {o.customer_phone}, {o.city}
                                        </span>
                                    </td>
                                    <td className={td}>
                                        <OrderStatusBadge status={o.status} label={o.status_label} />
                                    </td>
                                    <td className={td}>
                                        <PaymentBadge status={o.payment_status} label={o.payment_status_label} />
                                    </td>
                                    <td className={cn(td, 'text-right whitespace-nowrap tabular-nums')}>{formatMoney(o.total_minor, o.currency)}</td>
                                    <td className={cn(td, 'whitespace-nowrap text-stone')}>{formatDateTime(o.created_at, store.timezone)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                )}
                <Pagination page={orders} />
            </Panel>
        </>
    );
}

function StatusChip({ active, onClick, label, count }: { active: boolean; onClick: () => void; label: string; count?: number }) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={active}
            className={cn(
                'inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full border px-3.5 text-sm whitespace-nowrap',
                active ? 'border-ink bg-ink text-ivory' : 'border-line bg-white hover:border-stone',
            )}
        >
            {label}
            {count !== undefined && count > 0 && <span className={cn('tabular-nums', active ? 'text-ivory/70' : 'text-stone')}>{count}</span>}
        </button>
    );
}
