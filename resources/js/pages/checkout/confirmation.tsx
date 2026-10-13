import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { OrderTotals } from '@/components/store/order-totals';
import { useShared } from '@/hooks/use-shared';
import { formatMoney } from '@/lib/money';

interface ConfirmationProps {
    order: {
        reference: string;
        status_label: string;
        currency: string;
        items: { name: string; variant: string | null; quantity: number; line_total_minor: number }[];
        subtotal_minor: number;
        discount_minor: number;
        shipping_minor: number;
        total_minor: number;
        coupon_code: string | null;
        delivery: { name: string; phone: string; email: string | null; zone: string; city: string; address: string; notes: string | null };
    };
    checkout: { cod_instructions: string; confirmation_note: string };
}

export default function Confirmation({ order, checkout }: ConfirmationProps) {
    const { contact } = useShared();
    const firstName = order.delivery.name.split(' ')[0];

    const steps = [
        checkout.confirmation_note || 'We review your order and prepare it.',
        'We pack your order and hand it to the courier.',
        `You pay ${formatMoney(order.total_minor, order.currency)} in cash when it arrives.`,
    ];

    return (
        <div className="container-page py-12 lg:py-20">
            <div className="max-w-3xl">
                <p className="text-dune-deep">Order received</p>
                <h1 className="mt-2 font-display text-display">Thank you, {firstName}.</h1>
                <p className="mt-6 text-lg">
                    Your order reference is{' '}
                    <strong className="rounded bg-sand px-2 py-0.5 font-semibold tracking-wide select-all">{order.reference}</strong>. Keep it to follow your
                    order.
                </p>
            </div>

            <div className="mt-12 grid gap-12 lg:grid-cols-12">
                <section aria-labelledby="next-heading" className="lg:col-span-5">
                    <h2 id="next-heading" className="font-display text-2xl">
                        What happens next
                    </h2>
                    <ol className="mt-6 space-y-6">
                        {steps.map((step, i) => (
                            <li key={step} className="flex gap-4">
                                <span className="flex size-9 shrink-0 items-center justify-center rounded-full border border-dune font-display text-lg text-dune-deep">
                                    {i + 1}
                                </span>
                                <p className="pt-1.5">{step}</p>
                            </li>
                        ))}
                    </ol>
                    <div className="mt-10 flex flex-wrap gap-3">
                        <Button asChild variant="secondary">
                            <Link href="/track-order">Track this order</Link>
                        </Button>
                        {contact?.whatsapp && (
                            <Button asChild variant="ghost">
                                <a href={`https://wa.me/${contact.whatsapp}?text=${encodeURIComponent(`Order ${order.reference}`)}`} target="_blank" rel="noopener noreferrer">
                                    Questions? WhatsApp us
                                </a>
                            </Button>
                        )}
                    </div>
                </section>

                <section aria-labelledby="summary-heading" className="rounded-lg border border-line bg-white p-6 lg:col-span-6 lg:col-start-7">
                    <h2 id="summary-heading" className="font-display text-2xl">
                        Order summary
                    </h2>
                    <ul className="mt-4 divide-y divide-line border-y border-line">
                        {order.items.map((item, i) => (
                            <li key={i} className="flex justify-between gap-4 py-3 text-[0.95rem]">
                                <span>
                                    {item.name}
                                    {item.variant && <span className="text-stone"> — {item.variant}</span>}
                                    <span className="text-stone"> × {item.quantity}</span>
                                </span>
                                <span className="tabular-nums">{formatMoney(item.line_total_minor, order.currency)}</span>
                            </li>
                        ))}
                    </ul>
                    <OrderTotals
                        className="mt-5"
                        currency={order.currency}
                        subtotal={order.subtotal_minor}
                        discount={order.discount_minor}
                        shipping={order.shipping_minor}
                        total={order.total_minor}
                        couponCode={order.coupon_code}
                    />
                    <div className="mt-6 border-t border-line pt-5 text-[0.95rem]">
                        <h3 className="font-medium">Delivery to</h3>
                        <p className="mt-1 text-stone">
                            {order.delivery.name}, {order.delivery.phone}
                            <br />
                            {order.delivery.address}, {order.delivery.city} ({order.delivery.zone})
                        </p>
                        {order.delivery.notes && <p className="mt-2 text-stone">Notes: {order.delivery.notes}</p>}
                    </div>
                </section>
            </div>
        </div>
    );
}
