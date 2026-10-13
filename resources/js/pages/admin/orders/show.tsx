import { Link, useForm } from '@inertiajs/react';
import { AlertTriangle, MessageCircle, Phone, Printer } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import type { Option } from '@/types';

import { OrderStatusBadge, PageHeader, PaymentBadge, Panel, Table, td, th } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input, Label, Select, Textarea } from '@/components/ui/input';
import { OrderTotals } from '@/components/store/order-totals';
import { useShared } from '@/hooks/use-shared';
import { formatDateTime } from '@/lib/date';
import { formatMoney, toDecimalInput } from '@/lib/money';
import { cn } from '@/lib/utils';

interface OrderDetail {
    reference: string;
    status: string;
    status_label: string;
    payment_status: string;
    payment_status_label: string;
    created_at: string;
    currency: string;
    total_minor: number;
    subtotal_minor: number;
    discount_minor: number;
    shipping_minor: number;
    coupon_code: string | null;
    customer: { id: number | null; name: string; phone: string; phone_e164: string; whatsapp: string; email: string | null; orders_count: number | null };
    delivery: { zone: string; city: string; address: string; notes: string | null; courier_name: string | null; tracking_number: string | null };
    items: { id: number; product_id: number; name: string; variant: string | null; sku: string | null; quantity: number; unit_price_minor: number; regular_unit_price_minor: number; line_total_minor: number }[];
    payment: { method: string; status: string; status_label: string; collected_minor: number | null; collected_at: string | null; recorded_by: string | null; allowed: Option[] };
    transitions: Option[];
    risk_flags: string[];
    events: { id: number; type: string; to_label: string | null; note: string | null; user: string | null; created_at: string }[];
}

const DANGER = ['CANCELLED', 'FAILED_DELIVERY', 'RETURNED'];

/** What each move is called on its button. */
const ACTIONS: Record<string, string> = {
    CONFIRMED: 'Confirm order',
    PROCESSING: 'Start preparing',
    READY_TO_SHIP: 'Mark as packed',
    SHIPPED: 'Hand to courier',
    DELIVERED: 'Mark as delivered',
    CANCELLED: 'Cancel order',
    FAILED_DELIVERY: 'Delivery failed',
    RETURNED: 'Mark as returned',
};

export default function OrderShow({ order, canManage }: { order: OrderDetail; canManage: boolean }) {
    const { store } = useShared();
    const money = (v: number) => formatMoney(v, order.currency);

    return (
        <>
            <PageHeader
                back={{ href: '/admin/orders', label: 'Orders' }}
                title={`Order ${order.reference}`}
                description={
                    <span className="flex flex-wrap items-center gap-2">
                        <OrderStatusBadge status={order.status} label={order.status_label} />
                        <PaymentBadge status={order.payment_status} label={order.payment_status_label} />
                        <span className="text-sm">Placed {formatDateTime(order.created_at, store.timezone)}</span>
                    </span>
                }
                actions={
                    <Button asChild variant="secondary" size="sm">
                        <a href={`/admin/orders/${order.reference}/packing-slip`} target="_blank" rel="noopener">
                            <Printer aria-hidden="true" />
                            Packing slip
                        </a>
                    </Button>
                }
            />

            {order.risk_flags.length > 0 && (
                <div className="mb-6 flex gap-3 rounded-lg border border-dune/60 bg-[#F6EFE1] p-4 text-sm" role="note">
                    <AlertTriangle className="mt-0.5 size-4 shrink-0 text-dune-deep" aria-hidden="true" />
                    <div>
                        <p className="font-medium">Worth a phone call before sending</p>
                        <ul className="mt-1 list-disc pl-5 text-ink/80">
                            {order.risk_flags.map((f) => (
                                <li key={f}>{f}</li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    {canManage && order.transitions.length > 0 && <NextSteps order={order} />}

                    <Panel title="Items" padded={false}>
                        <Table>
                            <thead>
                                <tr>
                                    <th className={th}>Product</th>
                                    <th className={cn(th, 'text-right')}>Qty</th>
                                    <th className={cn(th, 'text-right')}>Price</th>
                                    <th className={cn(th, 'text-right')}>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {order.items.map((i) => (
                                    <tr key={i.id}>
                                        <td className={td}>
                                            <Link href={`/admin/products/${i.product_id}/edit`} className="hover:underline">
                                                {i.name}
                                            </Link>
                                            {i.variant && <span className="text-stone"> — {i.variant}</span>}
                                            {i.sku && <span className="block text-xs text-stone">{i.sku}</span>}
                                        </td>
                                        <td className={cn(td, 'text-right tabular-nums')}>{i.quantity}</td>
                                        <td className={cn(td, 'text-right tabular-nums')}>
                                            {money(i.unit_price_minor)}
                                            {i.regular_unit_price_minor > i.unit_price_minor && <s className="block text-xs text-stone">{money(i.regular_unit_price_minor)}</s>}
                                        </td>
                                        <td className={cn(td, 'text-right tabular-nums')}>{money(i.line_total_minor)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                        <div className="p-5">
                            <OrderTotals
                                currency={order.currency}
                                subtotal={order.subtotal_minor}
                                discount={order.discount_minor}
                                shipping={order.shipping_minor}
                                total={order.total_minor}
                                couponCode={order.coupon_code}
                                className="ml-auto max-w-sm"
                            />
                        </div>
                    </Panel>

                    <Panel title="History">
                        <ol className="space-y-4">
                            {[...order.events].reverse().map((e) => (
                                <li key={e.id} className="flex gap-3 text-sm">
                                    <span className={cn('mt-1.5 size-2 shrink-0 rounded-full', e.type === 'note' ? 'bg-dune' : e.type === 'payment' ? 'bg-aloe' : 'bg-ink')} aria-hidden="true" />
                                    <div>
                                        <p>
                                            {e.type === 'note' ? <span className="font-medium">Note</span> : e.to_label && <span className="font-medium">{e.to_label}</span>}
                                            {e.type === 'delivery' && <span className="font-medium">Delivery details</span>}
                                            <span className="text-stone">
                                                {' '}
                                                · {formatDateTime(e.created_at, store.timezone)}
                                                {e.user ? ` · ${e.user}` : ''}
                                            </span>
                                        </p>
                                        {e.note && <p className="mt-0.5 whitespace-pre-line text-ink/80">{e.note}</p>}
                                    </div>
                                </li>
                            ))}
                        </ol>
                        {canManage && <NoteForm reference={order.reference} />}
                    </Panel>
                </div>

                <div className="space-y-6">
                    <Panel title="Customer">
                        <p className="font-medium">{order.customer.name}</p>
                        <p className="mt-1 text-sm">{order.customer.phone}</p>
                        {order.customer.email && <p className="text-sm text-stone">{order.customer.email}</p>}
                        <div className="mt-3 flex flex-wrap gap-2">
                            <Button asChild size="sm" variant="secondary">
                                <a href={`tel:${order.customer.phone_e164}`}>
                                    <Phone aria-hidden="true" />
                                    Call
                                </a>
                            </Button>
                            <Button asChild size="sm" variant="secondary">
                                <a
                                    href={`https://wa.me/${order.customer.whatsapp}?text=${encodeURIComponent(`Hello ${order.customer.name.split(' ')[0]}, this is Aloe & Soul about your order ${order.reference}.`)}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <MessageCircle aria-hidden="true" />
                                    WhatsApp
                                </a>
                            </Button>
                        </div>
                        {order.customer.id && (
                            <Link href={`/admin/customers/${order.customer.id}`} className="mt-4 inline-block text-sm underline underline-offset-4">
                                {order.customer.orders_count === 1 ? 'First order' : `${order.customer.orders_count} orders in total`}
                            </Link>
                        )}
                    </Panel>

                    <Panel title="Delivery">
                        <dl className="space-y-2 text-sm">
                            <div>
                                <dt className="text-stone">Address</dt>
                                <dd>
                                    {order.delivery.address}
                                    <br />
                                    {order.delivery.city} ({order.delivery.zone})
                                </dd>
                            </div>
                            {order.delivery.notes && (
                                <div>
                                    <dt className="text-stone">Notes from the customer</dt>
                                    <dd className="whitespace-pre-line">{order.delivery.notes}</dd>
                                </div>
                            )}
                        </dl>
                        {canManage ? <DeliveryForm order={order} /> : null}
                    </Panel>

                    <Panel title="Payment" description="Cash on delivery">
                        <p className="text-sm">
                            <PaymentBadge status={order.payment.status} label={order.payment.status_label} />
                        </p>
                        {order.payment.collected_minor !== null && (
                            <p className="mt-2 text-sm text-stone">
                                {money(order.payment.collected_minor)} recorded {formatDateTime(order.payment.collected_at, store.timezone)}
                                {order.payment.recorded_by ? ` by ${order.payment.recorded_by}` : ''}.
                            </p>
                        )}
                        {canManage && order.payment.allowed.length > 0 && <PaymentForm order={order} />}
                    </Panel>
                </div>
            </div>
        </>
    );
}

function NextSteps({ order }: { order: OrderDetail }) {
    const [target, setTarget] = useState<Option | null>(null);
    const form = useForm({ to: '', note: '', restock: true, collect_payment: true });
    // Business-rule errors arrive under "status" (see OrderWorkflow).
    const errors = form.errors as Record<string, string | undefined>;

    const open = (o: Option) => {
        form.setData({ to: o.value, note: '', restock: true, collect_payment: true });
        form.clearErrors();
        setTarget(o);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(`/admin/orders/${order.reference}/transitions`, { preserveScroll: true, onSuccess: () => setTarget(null) });
    };

    return (
        <Panel title="Next step" description="Only the moves that make sense from the current status are offered.">
            <div className="flex flex-wrap gap-2">
                {order.transitions.map((t) => (
                    <Button key={t.value} variant={DANGER.includes(t.value) ? 'destructive-outline' : 'primary'} onClick={() => open(t)}>
                        {ACTIONS[t.value] ?? t.label}
                    </Button>
                ))}
            </div>
            <Dialog open={target !== null} onOpenChange={(o) => !o && setTarget(null)}>
                {target && (
                    <DialogContent title={`${ACTIONS[target.value] ?? target.label}?`} description={`Order ${order.reference} will move to “${target.label}”.`}>
                        <form onSubmit={submit} className="space-y-4">
                            {target.value === 'CANCELLED' && <p className="text-sm text-stone">Reserved stock is returned to inventory and any discount code use is released.</p>}
                            {target.value === 'RETURNED' && (
                                <div className="flex items-start gap-3">
                                    <Checkbox id="restock" checked={form.data.restock} onCheckedChange={(v) => form.setData('restock', v === true)} />
                                    <Label htmlFor="restock" className="font-normal">
                                        Put the returned items back into stock (they are in a sellable condition)
                                    </Label>
                                </div>
                            )}
                            {target.value === 'DELIVERED' && (
                                <div className="flex items-start gap-3">
                                    <Checkbox id="collect" checked={form.data.collect_payment} onCheckedChange={(v) => form.setData('collect_payment', v === true)} />
                                    <Label htmlFor="collect" className="font-normal">
                                        The courier collected {formatMoney(order.total_minor, order.currency)} in cash
                                    </Label>
                                </div>
                            )}
                            <Field label="Note" name="note" optional error={form.errors.note}>
                                {(p) => <Textarea {...p} rows={3} value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} placeholder="e.g. confirmed by phone" />}
                            </Field>
                            {(errors.status || errors.to) && (
                                <p className="text-sm text-clay" role="alert">
                                    {errors.status ?? errors.to}
                                </p>
                            )}
                            <div className="flex justify-end gap-2">
                                <Button type="button" variant="ghost" onClick={() => setTarget(null)}>
                                    Back
                                </Button>
                                <Button type="submit" variant={DANGER.includes(target.value) ? 'destructive' : 'primary'} loading={form.processing}>
                                    {ACTIONS[target.value] ?? 'Confirm'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                )}
            </Dialog>
        </Panel>
    );
}

function PaymentForm({ order }: { order: OrderDetail }) {
    const first = order.payment.allowed[0]?.value ?? '';
    const form = useForm({ payment_status: first, amount: toDecimalInput(order.total_minor, order.currency), note: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(`/admin/orders/${order.reference}/payment`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="mt-4 space-y-3 border-t border-line pt-4">
            <Field label="Record payment" name="payment_status" error={form.errors.payment_status}>
                {(p) => (
                    <Select {...p} value={form.data.payment_status} onChange={(e) => form.setData('payment_status', e.target.value)}>
                        {order.payment.allowed.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </Select>
                )}
            </Field>
            {form.data.payment_status === 'collected' && (
                <Field label={`Amount collected (${order.currency})`} name="amount" error={form.errors.amount}>
                    {(p) => <Input {...p} inputMode="decimal" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />}
                </Field>
            )}
            <Button type="submit" size="sm" loading={form.processing}>
                Save payment
            </Button>
        </form>
    );
}

function DeliveryForm({ order }: { order: OrderDetail }) {
    const form = useForm({ courier_name: order.delivery.courier_name ?? '', tracking_number: order.delivery.tracking_number ?? '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(`/admin/orders/${order.reference}/delivery`, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="mt-4 space-y-3 border-t border-line pt-4">
            <Field label="Courier" name="courier_name" optional error={form.errors.courier_name}>
                {(p) => <Input {...p} value={form.data.courier_name} onChange={(e) => form.setData('courier_name', e.target.value)} maxLength={80} />}
            </Field>
            <Field label="Tracking number" name="tracking_number" optional error={form.errors.tracking_number}>
                {(p) => <Input {...p} value={form.data.tracking_number} onChange={(e) => form.setData('tracking_number', e.target.value)} maxLength={80} />}
            </Field>
            <Button type="submit" size="sm" variant="secondary" loading={form.processing} disabled={!form.isDirty}>
                Save delivery details
            </Button>
        </form>
    );
}

function NoteForm({ reference }: { reference: string }) {
    const form = useForm({ note: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(`/admin/orders/${reference}/notes`, { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <form onSubmit={submit} className="mt-6 space-y-2 border-t border-line pt-5">
            <Field label="Internal note" name="note" error={form.errors.note} hint="Only visible to the team.">
                {(p) => <Textarea {...p} rows={2} value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} maxLength={2000} />}
            </Field>
            <Button type="submit" size="sm" variant="secondary" loading={form.processing} disabled={!form.data.note.trim()}>
                Add note
            </Button>
        </form>
    );
}
