import { Link, router, useForm } from '@inertiajs/react';
import { Banknote, Lock } from 'lucide-react';
import { type FormEvent } from 'react';

import type { Quote } from '@/types';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FormErrorSummary } from '@/components/ui/field';
import { Input, Select, Textarea } from '@/components/ui/input';
import { CouponForm } from '@/components/store/coupon-form';
import { OrderTotals } from '@/components/store/order-totals';
import { useShared } from '@/hooks/use-shared';
import { formatMoney } from '@/lib/money';

interface CheckoutProps {
    idempotencyKey: string;
    quote: Quote;
    zones: { id: number; name: string; fee_minor: number; free_over_minor: number | null; estimate: string | null }[];
    checkout: { open: boolean; closed_message: string; cod_instructions: string; confirmation_note: string };
    country: string;
}

export default function Checkout({ idempotencyKey, quote, zones, checkout, country }: CheckoutProps) {
    const { cart, store, errors: pageErrors } = useShared();
    const lines = (cart?.lines ?? []).filter((l) => !l.issue);
    const currency = store.currency;

    const form = useForm({
        name: '',
        phone: '',
        email: '',
        shipping_zone_id: quote.zone_id ? String(quote.zone_id) : '',
        city: '',
        address: '',
        notes: '',
        privacy: false,
    });

    const chooseZone = (id: string) => {
        form.setData('shipping_zone_id', id);
        // Recalculate delivery and total on the server; nothing is computed here.
        router.get('/checkout', id ? { zone: id } : {}, { only: ['quote'], preserveState: true, preserveScroll: true, replace: true });
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({ ...data, idempotency_key: idempotencyKey, expected_total: quote.total_minor ?? 0 }));
        form.post('/checkout', { preserveScroll: true });
    };

    const generalErrors = {
        cart: pageErrors.cart,
        total: pageErrors.total,
        coupon: pageErrors.coupon,
        general: pageErrors.general,
        idempotency_key: pageErrors.idempotency_key,
    };
    const isMorocco = country === 'MA';

    return (
        <div className="container-page py-10 lg:py-14">
            <div className="flex flex-wrap items-baseline justify-between gap-4">
                <h1 className="font-display text-display-sm">Checkout</h1>
                <Link href="/cart" className="text-sm underline underline-offset-4 hover:text-aloe">
                    Back to cart
                </Link>
            </div>

            {!checkout.open ? (
                <div className="mt-8 max-w-xl rounded-lg border border-line bg-white p-6">
                    <p className="font-display text-2xl">Ordering is paused</p>
                    <p className="mt-2 text-stone">{checkout.closed_message}</p>
                </div>
            ) : (
                <form onSubmit={submit} noValidate className="mt-8 grid gap-10 lg:grid-cols-12 lg:gap-14">
                    <div className="space-y-10 lg:col-span-7">
                        <FormErrorSummary errors={generalErrors} />

                        <fieldset className="space-y-5">
                            <legend className="mb-5 font-display text-2xl">Your details</legend>
                            <Field label="Full name" name="name" error={form.errors.name} required>
                                {(p) => <Input {...p} autoComplete="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} maxLength={120} />}
                            </Field>
                            <Field
                                label="Phone number"
                                name="phone"
                                error={form.errors.phone}
                                required
                                hint={isMorocco ? 'For example 06 12 34 56 78. The courier will call this number.' : 'The courier will call this number.'}
                            >
                                {(p) => (
                                    <Input
                                        {...p}
                                        type="tel"
                                        inputMode="tel"
                                        autoComplete="tel"
                                        value={form.data.phone}
                                        onChange={(e) => form.setData('phone', e.target.value)}
                                        maxLength={30}
                                    />
                                )}
                            </Field>
                            <Field label="Email" name="email" optional error={form.errors.email} hint="Only used for messages about this order.">
                                {(p) => <Input {...p} type="email" autoComplete="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} maxLength={255} />}
                            </Field>
                        </fieldset>

                        <fieldset className="space-y-5">
                            <legend className="mb-5 font-display text-2xl">Delivery</legend>
                            <Field label="Delivery area" name="shipping_zone_id" error={form.errors.shipping_zone_id} required>
                                {(p) => (
                                    <Select {...p} value={form.data.shipping_zone_id} onChange={(e) => chooseZone(e.target.value)}>
                                        <option value="">Choose your area</option>
                                        {zones.map((z) => (
                                            <option key={z.id} value={z.id}>
                                                {z.name}: {formatMoney(z.fee_minor, currency)}
                                                {z.estimate ? ` (${z.estimate})` : ''}
                                            </option>
                                        ))}
                                    </Select>
                                )}
                            </Field>
                            <div className="grid gap-5 sm:grid-cols-3">
                                <Field label="City" name="city" error={form.errors.city} required className="sm:col-span-1">
                                    {(p) => <Input {...p} autoComplete="address-level2" value={form.data.city} onChange={(e) => form.setData('city', e.target.value)} maxLength={80} />}
                                </Field>
                                <Field label="Address" name="address" error={form.errors.address} required hint="Street, number, building, floor." className="sm:col-span-2">
                                    {(p) => <Input {...p} autoComplete="street-address" value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} maxLength={255} />}
                                </Field>
                            </div>
                            <Field label="Delivery notes" name="notes" optional error={form.errors.notes} hint="Landmark, best time to call, anything that helps the courier.">
                                {(p) => <Textarea {...p} rows={3} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} maxLength={500} />}
                            </Field>
                        </fieldset>

                        <div className="rounded-lg bg-sand p-5">
                            <div className="flex gap-4">
                                <Banknote className="mt-0.5 size-6 shrink-0 text-dune-deep" strokeWidth={1.5} aria-hidden="true" />
                                <div>
                                    <p className="font-medium">Payment: cash on delivery</p>
                                    <p className="mt-1 text-[0.95rem] text-ink/80">{checkout.cod_instructions}</p>
                                    {checkout.confirmation_note && <p className="mt-1 text-[0.95rem] text-ink/80">{checkout.confirmation_note}</p>}
                                </div>
                            </div>
                        </div>

                        <div>
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="privacy"
                                    checked={form.data.privacy}
                                    onCheckedChange={(v) => form.setData('privacy', v === true)}
                                    aria-invalid={form.errors.privacy ? true : undefined}
                                    aria-describedby={form.errors.privacy ? 'privacy-error' : undefined}
                                    className="mt-0.5"
                                />
                                <label htmlFor="privacy" className="text-[0.95rem]">
                                    I agree that my details are used to deliver this order, as described in the{' '}
                                    <a href="/privacy-policy" target="_blank" rel="noopener" className="underline underline-offset-4">
                                        privacy policy
                                    </a>
                                    .
                                </label>
                            </div>
                            {form.errors.privacy && (
                                <p id="privacy-error" className="mt-2 text-sm text-clay" role="alert">
                                    {form.errors.privacy}
                                </p>
                            )}
                        </div>

                        <div className="lg:hidden">
                            <SubmitBlock processing={form.processing} total={quote.total_minor} currency={currency} />
                        </div>
                    </div>

                    <aside aria-label="Order summary" className="lg:col-span-5">
                        <div className="rounded-lg border border-line bg-white p-6 lg:sticky lg:top-28">
                            <h2 className="font-display text-2xl">Your order</h2>
                            <ul className="mt-4 divide-y divide-line border-y border-line">
                                {lines.map((l) => (
                                    <li key={l.id} className="flex justify-between gap-4 py-3 text-[0.95rem]">
                                        <span>
                                            {l.product_name}
                                            {l.variant_name && <span className="text-stone"> — {l.variant_name}</span>}
                                            <span className="text-stone"> × {l.quantity}</span>
                                        </span>
                                        <span className="shrink-0 tabular-nums">{formatMoney(l.line_total_minor, currency)}</span>
                                    </li>
                                ))}
                            </ul>
                            <div className="mt-5">
                                <CouponForm code={quote.coupon_code ?? cart?.coupon_code ?? null} error={quote.coupon_error} />
                            </div>
                            <OrderTotals
                                className="mt-6"
                                currency={currency}
                                subtotal={quote.subtotal_minor}
                                discount={quote.discount_minor}
                                shipping={quote.shipping_minor}
                                total={quote.total_minor}
                                couponCode={quote.coupon_code}
                            />
                            {quote.free_shipping_remaining_minor !== null && quote.free_shipping_remaining_minor > 0 && (
                                <p className="mt-3 text-sm text-stone">
                                    Add {formatMoney(quote.free_shipping_remaining_minor, currency)} more for free delivery to this area.
                                </p>
                            )}
                            <div className="mt-6 hidden lg:block">
                                <SubmitBlock processing={form.processing} total={quote.total_minor} currency={currency} />
                            </div>
                        </div>
                    </aside>
                </form>
            )}
        </div>
    );
}

function SubmitBlock({ processing, total, currency }: { processing: boolean; total: number | null; currency: string }) {
    return (
        <div>
            <Button type="submit" size="lg" className="w-full" loading={processing}>
                Place order
            </Button>
            <p className="mt-3 flex items-center justify-center gap-2 text-center text-sm text-stone">
                <Lock className="size-3.5" aria-hidden="true" />
                {total !== null ? `You will pay ${formatMoney(total, currency)} in cash on delivery.` : 'Choose a delivery area to see your total.'}
            </p>
        </div>
    );
}
