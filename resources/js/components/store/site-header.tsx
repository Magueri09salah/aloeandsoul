import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, Menu, Search, ShoppingBag } from 'lucide-react';
import { NavigationMenu } from 'radix-ui';
import { useEffect, useRef, useState, type FormEvent } from 'react';

import { Logo } from '@/components/brand/logo';
import { Motif } from '@/components/brand/motif';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { useCartUi } from '@/components/store/cart-context';
import { useShared } from '@/hooks/use-shared';
import { cn } from '@/lib/utils';

const LINKS = [
    { href: '/our-story', label: 'Our story' },
    { href: '/blog', label: 'Blog' },
    { href: '/faq', label: 'FAQ' },
    { href: '/contact', label: 'Contact' },
];

export function SiteHeader() {
    const { navigation = [], cart, contact } = useShared();
    const { url } = usePage();
    const cartUi = useCartUi();
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

    const isActive = (href: string) => url === href || url.startsWith(`${href}/`) || url.startsWith(`${href}?`);

    return (
        <header className="sticky top-0 z-40 border-b border-line/70 bg-ivory/95 backdrop-blur supports-[backdrop-filter]:bg-ivory/85">
            <div className="container-page flex h-[4.5rem] items-center gap-4">
                <button
                    type="button"
                    className="-ml-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand lg:hidden"
                    onClick={() => setMobileOpen(true)}
                    aria-label="Open menu"
                >
                    <Menu className="size-5" aria-hidden="true" />
                </button>

                <Logo className="mr-auto lg:mr-0" />

                <NavigationMenu.Root className="relative ml-10 hidden flex-1 lg:block" aria-label="Main">
                    <NavigationMenu.List className="flex items-center gap-1">
                        <NavigationMenu.Item>
                            <NavigationMenu.Trigger
                                className={cn(
                                    'group inline-flex h-10 items-center gap-1 rounded-full px-4 text-[0.95rem] hover:bg-sand data-[state=open]:bg-sand',
                                    isActive('/shop') && 'text-aloe',
                                )}
                            >
                                Shop
                                <ChevronDown className="size-4 transition-transform group-data-[state=open]:rotate-180" aria-hidden="true" />
                            </NavigationMenu.Trigger>
                            <NavigationMenu.Content className="absolute top-full left-0 mt-3 w-[min(46rem,80vw)] rounded-lg border border-line bg-ivory p-3 shadow-overlay data-[motion^=from-]:animate-in data-[motion^=from-]:fade-in data-[motion^=to-]:animate-out data-[motion^=to-]:fade-out">
                                <ul className="grid grid-cols-2 gap-1">
                                    {navigation.map((category) => (
                                        <li key={category.slug}>
                                            <NavigationMenu.Link asChild>
                                                <Link
                                                    href={`/shop/${category.slug}`}
                                                    className="flex items-start gap-4 rounded-md p-3 transition-colors hover:bg-sand focus-visible:bg-sand"
                                                >
                                                    <span className="flex size-14 shrink-0 items-center justify-center rounded-full p-2.5 text-dune" style={{ backgroundColor: category.tint }}>
                                                        <Motif name={category.motif} />
                                                    </span>
                                                    <span>
                                                        <span className="block font-medium">{category.name}</span>
                                                        {category.tagline && <span className="mt-0.5 block text-sm text-stone">{category.tagline}</span>}
                                                    </span>
                                                </Link>
                                            </NavigationMenu.Link>
                                        </li>
                                    ))}
                                </ul>
                                <div className="mt-2 border-t border-line px-3 pt-3 pb-1">
                                    <NavigationMenu.Link asChild>
                                        <Link href="/shop" className="text-sm font-medium underline underline-offset-4 hover:text-aloe">
                                            See all products
                                        </Link>
                                    </NavigationMenu.Link>
                                </div>
                            </NavigationMenu.Content>
                        </NavigationMenu.Item>
                        {LINKS.map((link) => (
                            <NavigationMenu.Item key={link.href}>
                                <NavigationMenu.Link asChild active={isActive(link.href)}>
                                    <Link
                                        href={link.href}
                                        className={cn(
                                            'inline-flex h-10 items-center rounded-full px-4 text-[0.95rem] hover:bg-sand',
                                            isActive(link.href) && 'text-aloe',
                                        )}
                                        aria-current={isActive(link.href) ? 'page' : undefined}
                                    >
                                        {link.label}
                                    </Link>
                                </NavigationMenu.Link>
                            </NavigationMenu.Item>
                        ))}
                    </NavigationMenu.List>
                </NavigationMenu.Root>

                <div className="flex items-center gap-1">
                    <button
                        type="button"
                        onClick={() => setSearchOpen(true)}
                        className="inline-flex size-11 items-center justify-center rounded-full hover:bg-sand"
                        aria-label="Search products"
                    >
                        <Search className="size-5" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        onClick={cartUi.open}
                        className="relative -mr-2 inline-flex size-11 items-center justify-center rounded-full hover:bg-sand"
                        aria-label={`Open cart, ${count} ${count === 1 ? 'item' : 'items'}`}
                    >
                        <ShoppingBag className="size-5" aria-hidden="true" />
                        {count > 0 && (
                            <span
                                className={cn(
                                    'absolute top-1 right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-ink px-1 text-[0.7rem] font-semibold text-ivory tabular-nums',
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

            <SearchDialog open={searchOpen} onOpenChange={setSearchOpen} />

            <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                <SheetContent side="left" title="Menu">
                    <nav aria-label="Main" className="flex-1 overflow-y-auto px-5 py-4">
                        <p className="text-sm text-stone">Shop</p>
                        <ul className="mt-2 border-b border-line pb-4">
                            {navigation.map((c) => (
                                <li key={c.slug}>
                                    <Link
                                        href={`/shop/${c.slug}`}
                                        onClick={() => setMobileOpen(false)}
                                        className="flex items-center gap-3 py-2.5 text-lg"
                                    >
                                        <span className="size-3 rounded-full" style={{ backgroundColor: c.tint }} aria-hidden="true" />
                                        {c.name}
                                    </Link>
                                </li>
                            ))}
                            <li>
                                <Link href="/shop" onClick={() => setMobileOpen(false)} className="block py-2.5 text-lg underline underline-offset-4">
                                    All products
                                </Link>
                            </li>
                        </ul>
                        <ul className="mt-4 space-y-1">
                            {[...LINKS, { href: '/track-order', label: 'Track an order' }].map((l) => (
                                <li key={l.href}>
                                    <Link href={l.href} onClick={() => setMobileOpen(false)} className="block py-2 font-display text-2xl">
                                        {l.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>
                    {contact?.whatsapp && (
                        <div className="border-t border-line p-5">
                            <Button asChild variant="secondary" className="w-full">
                                <a href={`https://wa.me/${contact.whatsapp}`} target="_blank" rel="noopener noreferrer">
                                    Message us on WhatsApp
                                </a>
                            </Button>
                        </div>
                    )}
                </SheetContent>
            </Sheet>
        </header>
    );
}

function SearchDialog({ open, onOpenChange }: { open: boolean; onOpenChange: (open: boolean) => void }) {
    const [q, setQ] = useState('');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const term = q.trim();
        onOpenChange(false);
        router.get('/shop', term ? { q: term } : {});
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent title="Search" description="Find a product by name." className="top-24 translate-y-0 sm:top-32">
                <form onSubmit={submit} role="search" className="flex gap-2">
                    <label htmlFor="site-search" className="sr-only">
                        Search products
                    </label>
                    <Input
                        id="site-search"
                        type="search"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Aloe vera gel, shampoo…"
                        maxLength={100}
                        autoFocus
                    />
                    <Button type="submit">Search</Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
