import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { useCartUi } from '@/components/store/cart-context';
import { CartLines } from '@/components/store/cart-lines';
import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';

export function CartDrawer() {
    const { isOpen, setOpen } = useCartUi();
    const { cart, navigation } = useShared();
    const { t, to, money } = useI18n();
    const lines = cart?.lines ?? [];

    return (
        <Sheet open={isOpen} onOpenChange={setOpen}>
            <SheetContent title={t('Your cart')} side="right">
                {lines.length === 0 ? (
                    <div className="flex flex-1 flex-col justify-center px-6 pb-16">
                        <p className="font-display text-3xl leading-tight">{t('Your cart is empty.')}</p>
                        <p className="mt-3 text-stone">{t('Start with one of our collections.')}</p>
                        <ul className="mt-6 space-y-2">
                            {navigation?.map((c) => (
                                <li key={c.slug}>
                                    <Link href={to(`/shop/${c.slug}`)} onClick={() => setOpen(false)} className="text-lg underline-offset-4 hover:underline">
                                        {c.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                ) : (
                    <>
                        <div className="flex-1 overflow-y-auto px-5">
                            <CartLines lines={lines} compact />
                        </div>
                        <div className="border-t border-line bg-white px-5 pt-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
                            <div className="flex items-baseline justify-between">
                                <span className="text-stone">{t('Subtotal')}</span>
                                <span className="text-lg tabular-nums">{money(cart?.subtotal_minor ?? 0)}</span>
                            </div>
                            <p className="mt-1 text-sm text-stone">{t('Delivery is added at checkout. You pay in cash when your order arrives.')}</p>
                            <div className="mt-4 grid gap-2">
                                <Button asChild size="lg" className={cart?.has_issues ? 'pointer-events-none opacity-50' : undefined}>
                                    <Link href={to('/checkout')} onClick={() => setOpen(false)} aria-disabled={cart?.has_issues || undefined}>
                                        {t('Go to checkout')}
                                    </Link>
                                </Button>
                                <Button asChild variant="ghost">
                                    <Link href={to('/cart')} onClick={() => setOpen(false)}>
                                        {t('View cart')}
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
