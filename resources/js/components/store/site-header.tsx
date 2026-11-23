import { Link, router, usePage } from '@inertiajs/react';
import { Menu, Search, ShoppingBag } from 'lucide-react';
import { useEffect, useRef, useState, type FormEvent } from 'react';

import { Logo } from '@/components/brand/logo';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { useCartUi } from '@/components/store/cart-context';
import { LanguageList, LanguageToggle } from '@/components/store/language-switcher';
import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export function SiteHeader() {
    const { navigation = [], cart, contact } = useShared();
    const { url } = usePage();
    const cartUi = useCartUi();
    const { t, tc, to } = useI18n();
    const links = [
        { href: '/blog', label: t('Blog') },
        { href: '/faq', label: t('FAQ') },
        { href: '/contact', label: t('Contact') },
    ];
    const [mobileOpen, setMobileOpen] = useState(false);
    const [searchOpen, setSearchOpen] = useState(false);
    const count = cart?.count ?? 0;

    // Pulse the cart count when it changes, to confirm an add.
    const [pulse, setPulse] = useState(false);
    const previous = useRef(count);
    useEffect(() => {
        if (count > previous.current) {
            setPulse(true);
            const t = setTimeout(() => setPulse(false), 500);
            previous.current = count;
            return () => clearTimeout(t);
        }
        previous.current = count;
    }, [count]);

    // Once the page scrolls, the category row folds away and only the top row stays.
    const [compact, setCompact] = useState(false);
    useEffect(() => {
        const onScroll = () => setCompact(window.scrollY > 120);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const isActive = (path: string) => {
        const href = to(path);
        return url === href || url.startsWith(`${href}/`) || url.startsWith(`${href}?`);
    };
    const shopActive = url === to('/shop') || url.startsWith(`${to('/shop')}?`);
    const searchTerm = new URLSearchParams(url.split('?')[1] ?? '').get('q') ?? '';

    const navLink = (active: boolean) =>
        cn(
            'inline-flex h-12 items-center text-[0.95rem] font-medium whitespace-nowrap text-stone underline-offset-[6px] transition-colors hover:text-ink hover:underline',
            active && 'text-ink underline decoration-dune',
        );

    return (
        <header className="sticky top-0 z-40 border-b border-line/70 bg-ivory/95 backdrop-blur supports-[backdrop-filter]:bg-ivory/90">
            <div className="container-page flex h-[4.5rem] items-center gap-2 sm:gap-4 lg:h-20 lg:gap-8">
                <button
                    type="button"
                    className="-ml-2 inline-flex size-11 shrink-0 items-center justify-center rounded-full hover:bg-sand lg:hidden"
                    onClick={() => setMobileOpen(true)}
                    aria-label={t('Open menu')}
                >
                    <Menu className="size-5" aria-hidden="true" />
                </button>

                {/* Below 360px there is room for the mark only; the name returns from there up. */}
                <Logo href={to('/')} className="mr-auto lg:mr-0" wordmarkClassName="max-[359px]:hidden" />

                {/* Keyed by the term shown, so it follows the page (e.g. after "Clear filters"). */}
                <HeaderSearch
                    key={searchTerm}
                    initial={searchTerm}
                    className="mx-auto hidden max-w-xl flex-1 lg:flex"
                />

                <div className="flex shrink-0 items-center sm:gap-1 lg:gap-2">
                    <Link
                        href={to('/our-story')}
                        className={cn(
                            'hidden px-2 text-[0.95rem] text-stone hover:text-ink lg:inline-block',
                            isActive('/our-story') && 'text-ink',
                        )}
                        aria-current={isActive('/our-story') ? 'page' : undefined}
                    >
                        {t('Our story')}
                    </Link>
                    <LanguageToggle className="text-stone hover:text-ink" />
                    <button
                        type="button"
                        onClick={() => setSearchOpen(true)}
                        className="inline-flex size-11 shrink-0 items-center justify-center rounded-full hover:bg-sand lg:hidden"
                        aria-label={t('Search products')}
                    >
                        <Search className="size-5" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        onClick={cartUi.open}
                        className="relative ml-1 inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-stone text-white transition-colors hover:bg-ink"
                        aria-label={tc('Open cart, :count item|Open cart, :count items', count)}
                    >
                        <ShoppingBag className="size-[1.15rem]" aria-hidden="true" />
                        {count > 0 && (
                            <span
                                className={cn(
                                    'absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-dune-deep px-1 text-[0.7rem] font-semibold text-white tabular-nums ring-2 ring-ivory',
                                    pulse && 'motion-safe:animate-count-pulse',
                                )}
                                aria-hidden="true"
                            >
                                {count}
                            </span>
                        )}
                    </button>
                </div>
            </div>

            {/* Category row (desktop). Folded away once the page scrolls. */}
            <div
                className={cn(
                    'hidden transition-[grid-template-rows] duration-300 ease-(--ease-out-soft) lg:grid',
                    compact ? 'grid-rows-[0fr]' : 'grid-rows-[1fr]',
                )}
                inert={compact}
            >
                <div className="overflow-hidden">
                    <nav aria-label={t('Main')} className="container-page">
                        <ul className="flex flex-wrap items-center justify-center gap-x-6 border-t border-line xl:gap-x-8">
                            <li>
                                <Link
                                    href={to('/shop')}
                                    className={navLink(shopActive)}
                                    aria-current={shopActive ? 'page' : undefined}
                                >
                                    {t('All products')}
                                </Link>
                            </li>
                            {navigation.map((category) => {
                                const active = isActive(`/shop/${category.slug}`);
                                return (
                                    <li key={category.slug}>
                                        <Link
                                            href={to(`/shop/${category.slug}`)}
                                            className={navLink(active)}
                                            aria-current={active ? 'page' : undefined}
                                        >
                                            {category.name}
                                        </Link>
                                    </li>
                                );
                            })}
                            {links.map((link) => (
                                <li key={link.href}>
                                    <Link
                                        href={to(link.href)}
                                        className={navLink(isActive(link.href))}
                                        aria-current={isActive(link.href) ? 'page' : undefined}
                                    >
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>
                </div>
            </div>

            <SearchDialog open={searchOpen} onOpenChange={setSearchOpen} />

            <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                <SheetContent side="left" title={t('Menu')}>
                    <nav aria-label={t('Main')} className="flex-1 overflow-y-auto px-5 py-4">
                        <p className="text-xs font-semibold tracking-[0.04em] text-stone uppercase">{t('Shop')}</p>
                        <ul className="mt-2 border-b border-line pb-4">
                            {navigation.map((c) => (
                                <li key={c.slug}>
                                    <Link
                                        href={to(`/shop/${c.slug}`)}
                                        onClick={() => setMobileOpen(false)}
                                        className="flex items-center gap-3 py-2.5 text-lg"
                                    >
                                        <span
                                            className="size-3 rounded-full"
                                            style={{ backgroundColor: c.tint }}
                                            aria-hidden="true"
                                        />
                                        {c.name}
                                    </Link>
                                </li>
                            ))}
                            <li>
                                <Link
                                    href={to('/shop')}
                                    onClick={() => setMobileOpen(false)}
                                    className="block py-2.5 text-lg underline underline-offset-4"
                                >
                                    {t('All products')}
                                </Link>
                            </li>
                        </ul>
                        <ul className="mt-4 space-y-1">
                            {[
                                { href: '/our-story', label: t('Our story') },
                                ...links,
                                { href: '/track-order', label: t('Track an order') },
                            ].map((l) => (
                                <li key={l.href}>
                                    <Link
                                        href={to(l.href)}
                                        onClick={() => setMobileOpen(false)}
                                        className="block py-2 font-display text-2xl"
                                    >
                                        {l.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                        <LanguageList className="mt-6 border-t border-line pt-4" linkClassName="inline-block py-1.5" />
                    </nav>
                    {contact?.whatsapp && (
                        <div className="border-t border-line p-5">
                            <Button asChild variant="secondary" className="w-full">
                                <a href={`https://wa.me/${contact.whatsapp}`} target="_blank" rel="noopener noreferrer">
                                    {t('Message us on WhatsApp')}
                                </a>
                            </Button>
                        </div>
                    )}
                </SheetContent>
            </Sheet>
        </header>
    );
}

/** The search bar in the header (desktop): searches the shop by product name. */
function HeaderSearch({ initial, className }: { initial: string; className?: string }) {
    const { t, to } = useI18n();
    const [q, setQ] = useState(initial);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const term = q.trim();
        router.get(to('/shop'), term ? { q: term } : {});
    };

    return (
        <form onSubmit={submit} role="search" className={cn('relative items-center', className)}>
            <label htmlFor="header-search" className="sr-only">
                {t('Search products')}
            </label>
            <input
                id="header-search"
                type="search"
                value={q}
                onChange={(e) => setQ(e.target.value)}
                placeholder={t('Search')}
                maxLength={100}
                className="h-12 w-full rounded-full border border-stone/40 bg-transparent pr-14 pl-5 text-[0.95rem] placeholder:text-stone/80 focus-visible:border-stone focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-aloe [&::-webkit-search-cancel-button]:hidden"
            />
            <button
                type="submit"
                className="absolute right-1.5 inline-flex size-9 items-center justify-center rounded-full bg-mist text-stone transition-colors hover:bg-sand hover:text-ink"
                aria-label={t('Search')}
            >
                <Search className="size-4" aria-hidden="true" />
            </button>
        </form>
    );
}

function SearchDialog({ open, onOpenChange }: { open: boolean; onOpenChange: (open: boolean) => void }) {
    const [q, setQ] = useState('');
    const { t, to } = useI18n();

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const term = q.trim();
        onOpenChange(false);
        router.get(to('/shop'), term ? { q: term } : {});
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                title={t('Search')}
                description={t('Find a product by name.')}
                className="top-24 translate-y-0 sm:top-32"
            >
                <form onSubmit={submit} role="search" className="flex gap-2">
                    <label htmlFor="site-search" className="sr-only">
                        {t('Search products')}
                    </label>
                    <Input
                        id="site-search"
                        type="search"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder={t('Aloe vera gel, shampoo…')}
                        maxLength={100}
                        autoFocus
                    />
                    <Button type="submit">{t('Search')}</Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
