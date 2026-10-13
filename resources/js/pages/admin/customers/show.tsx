import { Link, router } from '@inertiajs/react';

import { OrderStatusBadge, PageHeader, PaymentBadge, Panel, Stat, Table, td, th } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/dialog';
import { useShared } from '@/hooks/use-shared';
import { formatDate, formatDateTime } from '@/lib/date';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';

interface Props {
    customer: {
        id: number;
        name: string;
        phone: string | null;
        whatsapp: string | null;
        email: string | null;
        city: string | null;
        first_order_at: string | null;
        last_order_at: string | null;
        anonymized_at: string | null;
    };
    stats: { orders: number; delivered: number; failed_or_returned: number; cancelled: number; collected_minor: number };
    orders: { reference: string; total_minor: number; currency: string; status: string; status_label: string; payment_status: string; payment_status_label: string; created_at: string }[];
    currency: string;
    canAnonymize: boolean;
}

export default function CustomerShow({ customer, stats, orders, currency, canAnonymize }: Props) {
    const { store } = useShared();

    return (
        <>
            <PageHeader
                back={{ href: '/admin/customers', label: 'Customers' }}
                title={customer.name}
                description={customer.anonymized_at ? `Personal data removed on ${formatDate(customer.anonymized_at, store.timezone)}.` : `Customer since ${formatDate(customer.first_order_at, store.timezone)}`}
                actions={
                    customer.whatsapp && (
                        <Button asChild variant="secondary" size="sm">
                            <a href={`https://wa.me/${customer.whatsapp}`} target="_blank" rel="noopener noreferrer">
                                WhatsApp
                            </a>
                        </Button>
                    )
                }
            />

            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <Stat label="Orders" value={stats.orders} />
                <Stat label="Delivered" value={stats.delivered} />
                <Stat label="Failed or returned" value={stats.failed_or_returned} />
                <Stat label="Cash collected" value={formatMoney(stats.collected_minor, currency)} />
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Panel title="Orders" padded={false} className="lg:col-span-2">
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>Order</th>
                                <th className={th}>Status</th>
                                <th className={th}>Payment</th>
                                <th className={cn(th, 'text-right')}>Total</th>
                                <th className={th}>Placed</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.map((o) => (
                                <tr key={o.reference}>
                                    <td className={td}>
                                        <Link href={`/admin/orders/${o.reference}`} className="font-medium hover:underline">
                                            {o.reference}
                                        </Link>
                                    </td>
                                    <td className={td}>
                                        <OrderStatusBadge status={o.status} label={o.status_label} />
                                    </td>
                                    <td className={td}>
                                        <PaymentBadge status={o.payment_status} label={o.payment_status_label} />
                                    </td>
                                    <td className={cn(td, 'text-right tabular-nums')}>{formatMoney(o.total_minor, o.currency)}</td>
                                    <td className={cn(td, 'whitespace-nowrap text-stone')}>{formatDateTime(o.created_at, store.timezone)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                </Panel>

                <div className="space-y-6">
                    <Panel title="Contact">
                        <dl className="space-y-2 text-sm">
                            <div>
                                <dt className="text-stone">Phone</dt>
                                <dd>{customer.phone ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-stone">Email</dt>
                                <dd>{customer.email ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-stone">City</dt>
                                <dd>{customer.city ?? '—'}</dd>
                            </div>
                        </dl>
                    </Panel>

                    {canAnonymize && !customer.anonymized_at && (
                        <Panel title="Remove personal data" description="For a customer who asks to be forgotten.">
                            <p className="text-sm text-stone">Name, phone, email and addresses are removed from this customer and all their orders. Order amounts are kept for your accounts.</p>
                            <ConfirmDialog
                                trigger={
                                    <Button variant="destructive-outline" size="sm" className="mt-3">
                                        Remove personal data
                                    </Button>
                                }
                                title="Remove this customer’s personal data?"
                                description="This cannot be undone. Orders still in progress must be completed or cancelled first."
                                confirmLabel="Remove personal data"
                                onConfirm={() => router.post(`/admin/customers/${customer.id}/anonymize`, {}, { preserveScroll: true })}
                            />
                        </Panel>
                    )}
                </div>
            </div>
        </>
    );
}
