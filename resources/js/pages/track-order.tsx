import { useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useShared } from '@/hooks/use-shared';
import { formatDate } from '@/lib/date';
import { formatMoney } from '@/lib/money';

interface TrackedOrder {
    reference: string;
    status: string;
    status_label: string;
    status_message: string;
    placed_at: string;
    currency: string;
    total_minor: number;
    items: { name: string; variant: string | null; quantity: number }[];
}

export default function TrackOrder({ order }: { order: TrackedOrder | null }) {
    const { store } = useShared();
    const form = useForm({ reference: '', phone: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/track-order', { preserveScroll: true });
    };

    return (
        <div className="container-page grid gap-12 py-12 lg:grid-cols-12 lg:py-20">
            <div className="lg:col-span-5">
                <h1 className="font-display text-display-sm">Track your order</h1>
                <p className="mt-4 text-lg text-stone">Enter the reference shown after you ordered and the phone number you used.</p>
                <form onSubmit={submit} noValidate className="mt-8 space-y-5">
                    <Field label="Order reference" name="reference" error={form.errors.reference} hint="For example AS-2610-K7Q3X" required>
                        {(p) => (
                            <Input
                                {...p}
                                value={form.data.reference}
                                onChange={(e) => form.setData('reference', e.target.value.toUpperCase())}
                                autoCapitalize="characters"
                                autoComplete="off"
                                maxLength={20}
                            />
                        )}
                    </Field>
                    <Field label="Phone number" name="phone" error={form.errors.phone} required>
                        {(p) => <Input {...p} type="tel" inputMode="tel" autoComplete="tel" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} maxLength={30} />}
                    </Field>
                    <Button type="submit" loading={form.processing}>
                        Find my order
                    </Button>
                </form>
            </div>

            <div className="lg:col-span-6 lg:col-start-7" aria-live="polite">
                {order && (
                    <section className="rounded-lg border border-line bg-white p-6">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h2 className="font-display text-2xl">Order {order.reference}</h2>
                            <Badge tone={order.status === 'DELIVERED' ? 'success' : order.status === 'CANCELLED' ? 'danger' : 'info'}>{order.status_label}</Badge>
                        </div>
                        <p className="mt-2 text-sm text-stone">Placed on {formatDate(order.placed_at, store.timezone)}</p>
                        <p className="mt-5 text-lg">{order.status_message}</p>
                        <ul className="mt-5 divide-y divide-line border-y border-line text-[0.95rem]">
                            {order.items.map((item, i) => (
                                <li key={i} className="py-2.5">
                                    {item.name}
                                    {item.variant && <span className="text-stone"> — {item.variant}</span>}
                                    <span className="text-stone"> × {item.quantity}</span>
                                </li>
                            ))}
                        </ul>
                        <p className="mt-4 flex justify-between">
                            <span className="text-stone">To pay on delivery</span>
                            <span className="font-medium tabular-nums">{formatMoney(order.total_minor, order.currency)}</span>
                        </p>
                    </section>
                )}
            </div>
        </div>
    );
}
