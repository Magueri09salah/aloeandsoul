import { Link } from '@inertiajs/react';

import { NewsletterForm } from '@/components/store/newsletter-form';
import { useShared } from '@/hooks/use-shared';

const SOCIAL_LABELS: Record<string, string> = {
    instagram: 'Instagram',
    facebook: 'Facebook',
    tiktok: 'TikTok',
    youtube: 'YouTube',
};

export function SiteFooter() {
    const { store, contact, social = {}, navigation = [], newsletter_enabled } = useShared();
    const year = new Date().getFullYear();

    const linkClass = 'text-ivory/80 hover:text-ivory hover:underline hover:underline-offset-4';

    return (
        <footer className="mt-24 bg-ink text-ivory lg:mt-32">
            <div className="container-page grid gap-12 pt-16 pb-10 lg:grid-cols-12 lg:gap-8 lg:pt-20">
                <div className="lg:col-span-5">
                    <p className="font-display text-3xl leading-tight sm:text-4xl">{store.tagline}</p>
                    {newsletter_enabled && (
                        <div className="mt-8">
                            <p className="mb-3 text-ivory/80">News about new products and the occasional story, by email.</p>
                            <NewsletterForm tone="dark" />
                        </div>
                    )}
                </div>

                <nav aria-label="Shop" className="lg:col-span-2 lg:col-start-7">
                    <h2 className="text-sm text-dune">Shop</h2>
                    <ul className="mt-4 space-y-2.5">
                        {navigation.map((c) => (
                            <li key={c.slug}>
                                <Link href={`/shop/${c.slug}`} className={linkClass}>
                                    {c.name}
                                </Link>
                            </li>
                        ))}
                        <li>
                            <Link href="/shop" className={linkClass}>
                                All products
                            </Link>
                        </li>
                    </ul>
                </nav>

                <nav aria-label="Help" className="lg:col-span-2">
                    <h2 className="text-sm text-dune">Help</h2>
                    <ul className="mt-4 space-y-2.5">
                        <li><Link href="/track-order" className={linkClass}>Track an order</Link></li>
                        <li><Link href="/faq" className={linkClass}>FAQ</Link></li>
                        <li><Link href="/shipping-returns" className={linkClass}>Shipping and returns</Link></li>
                        <li><Link href="/contact" className={linkClass}>Contact</Link></li>
                    </ul>
                </nav>

                <div className="lg:col-span-2">
                    <h2 className="text-sm text-dune">Aloe &amp; Soul</h2>
                    <ul className="mt-4 space-y-2.5">
                        <li><Link href="/our-story" className={linkClass}>Our story</Link></li>
                        {contact?.email && (
                            <li><a href={`mailto:${contact.email}`} className={linkClass}>{contact.email}</a></li>
                        )}
                        {contact?.phone_display && (
                            <li><a href={`tel:${contact.phone}`} className={linkClass}>{contact.phone_display}</a></li>
                        )}
                        {contact?.whatsapp && (
                            <li>
                                <a href={`https://wa.me/${contact.whatsapp}`} target="_blank" rel="noopener noreferrer" className={linkClass}>
                                    WhatsApp
                                </a>
                            </li>
                        )}
                        {Object.entries(social).map(([network, href]) => (
                            <li key={network}>
                                <a href={href} target="_blank" rel="noopener noreferrer" className={linkClass}>
                                    {SOCIAL_LABELS[network] ?? network}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            <div className="container-page flex flex-col gap-4 border-t border-ivory/15 py-6 text-sm text-ivory/65 lg:flex-row lg:items-center lg:justify-between">
                <p>
                    © {year} {store.name}
                    {contact?.locations?.length ? `. ${contact.locations.join(', ')}.` : '.'} Payment in cash on delivery.
                </p>
                <ul className="flex flex-wrap gap-x-5 gap-y-2">
                    <li><Link href="/privacy-policy" className="hover:text-ivory">Privacy</Link></li>
                    <li><Link href="/terms" className="hover:text-ivory">Terms of sale</Link></li>
                    <li><Link href="/legal-notice" className="hover:text-ivory">Legal notice</Link></li>
                </ul>
            </div>
        </footer>
    );
}
