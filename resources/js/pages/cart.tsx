import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { CartLines } from '@/components/store/cart-lines';
import { CouponForm } from '@/components/store/coupon-form';
import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';

export default function Cart() {
    const { cart, navigation = [] } = useShared();
    const { t, to, money } = useI18n();
    const lines = cart?.lines ?? [];

    return (
        <div className="container-page py-10 lg:py-16">
            <h1 className="font-display text-display-sm">{t('Your cart')}</h1>

            {lines.length === 0 ? (
                <div className="mt-10 max-w-xl">
                    <p className="text-lg text-stone">{t('Your cart is empty. Start with one of our collections.')}</p>
                    <ul className="mt-6 flex flex-wrap gap-2">
                        {navigation.map((c) => (
                            <li key={c.slug}>
                                <Link href={to(`/shop/${c.slug}`)} className="inline-flex h-10 items-center rounded-full border border-line bg-white px-4 hover:border-ink">
                                    {c.name}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            ) : (
                <div className="mt-8 grid gap-10 lg:grid-cols-12 lg:gap-14">
                    <section aria-label={t('Items')} className="lg:col-span-7">
                        <CartLines lines={lines} />
                    </section>
                    <aside aria-label={t('Summary')} className="lg:col-span-5">
                        <div className="rounded-lg border border-line bg-white p-6 lg:sticky lg:top-28">
                            <div className="flex items-baseline justify-between">
                                <span className="text-stone">{t('Subtotal')}</span>
                                <span className="font-display text-3xl tabular-nums">{money(cart?.subtotal_minor ?? 0)}</span>
                            </div>
                            <p className="mt-2 text-sm text-stone">{t('The delivery fee for your area is shown at checkout. You pay in cash when your order arrives.')}</p>
                            <div className="mt-6">
                                <CouponForm code={cart?.coupon_code ?? null} />
                            </div>
                            {cart?.has_issues && (
                                <p className="mt-6 rounded-(--radius-control) bg-clay-soft px-4 py-3 text-sm text-clay" role="alert">
                                    {t('Some items need your attention before you can check out.')}
                                </p>
                            )}
                            <Button asChild size="lg" className={`mt-6 w-full ${cart?.has_issues ? 'pointer-events-none opacity-50' : ''}`}>
                                <Link href={to('/checkout')} aria-disabled={cart?.has_issues || undefined}>
                                    {t('Go to checkout')}
                                </Link>
                            </Button>
                            <Link href={to('/shop')} className="mt-4 block text-center text-sm underline underline-offset-4 hover:text-aloe">
                                {t('Continue shopping')}
                            </Link>
                        </div>
                    </aside>
                </div>
            )}
        </div>
    );
}
