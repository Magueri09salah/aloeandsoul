import { Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import type { Paginated } from '@/types';

import { EmptyState, PageHeader, Pagination, Panel, Table, td, th } from '@/components/admin/ui';
import { Input } from '@/components/ui/input';
import { useShared } from '@/hooks/use-shared';
import { formatDate } from '@/lib/date';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';

interface Row {
    id: number;
    name: string;
    phone: string;
    city: string | null;
    orders_count: number;
    delivered_value_minor: number;
    last_order_at: string | null;
    anonymized: boolean;
}

export default function Customers({ customers, filters, currency }: { customers: Paginated<Row>; filters: { q: string }; currency: string }) {
    const { store } = useShared();
    const [q, setQ] = useState(filters.q);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/admin/customers', q ? { q } : {}, { preserveState: true, replace: true });
    };

    return (
        <>
            <PageHeader title="Customers" description="Created automatically from orders, identified by phone number. No accounts or passwords." />
            <Panel padded={false}>
                <form onSubmit={submit} className="border-b border-line p-4">
                    <div className="relative max-w-md">
                        <label htmlFor="q" className="sr-only">
                            Search customers
                        </label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone" aria-hidden="true" />
                        <Input id="q" type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Name or phone" className="h-10 pl-9" />
                    </div>
                </form>
                {customers.data.length === 0 ? (
                    <EmptyState title="No customers found." />
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>Customer</th>
                                <th className={th}>City</th>
                                <th className={cn(th, 'text-right')}>Orders</th>
                                <th className={cn(th, 'text-right')}>Delivered value</th>
                                <th className={th}>Last order</th>
                            </tr>
                        </thead>
                        <tbody>
                            {customers.data.map((c) => (
                                <tr key={c.id} className="hover:bg-panel/50">
                                    <td className={td}>
                                        <Link href={`/admin/customers/${c.id}`} className={cn('font-medium hover:underline', c.anonymized && 'text-stone')}>
                                            {c.name}
                                        </Link>
                                        <span className="block text-xs text-stone">{c.phone}</span>
                                    </td>
                                    <td className={td}>{c.city ?? '—'}</td>
                                    <td className={cn(td, 'text-right tabular-nums')}>{c.orders_count}</td>
                                    <td className={cn(td, 'text-right tabular-nums')}>{formatMoney(c.delivered_value_minor, currency)}</td>
                                    <td className={cn(td, 'text-stone')}>{formatDate(c.last_order_at, store.timezone)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                )}
                <Pagination page={customers} />
            </Panel>
        </>
    );
}
