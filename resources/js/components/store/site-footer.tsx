import { Link } from '@inertiajs/react';
import { Banknote } from 'lucide-react';

import { LanguageList } from '@/components/store/language-switcher';
import { NewsletterForm } from '@/components/store/newsletter-form';
import { Accented } from '@/components/store/section-heading';
import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';

const SOCIAL_LABELS: Record<string, string> = {
    instagram: 'Instagram',
    facebook: 'Facebook',
    tiktok: 'TikTok',
    youtube: 'YouTube',
};

export function SiteFooter() {
    const { store, contact, social = {}, navigation = [], newsletter_enabled } = useShared();
    const { t, to } = useI18n();
    const year = new Date().getFullYear();

    const linkClass = 'text-white/85 hover:text-white hover:underline hover:underline-offset-4';
    const headingClass = 'font-semibold text-white';

    return (
        <footer className="mt-24 bg-aloe text-white lg:mt-32">
            <div className="container-page grid gap-12 pt-16 pb-12 lg:grid-cols-12 lg:gap-8 lg:pt-20">
                <div className="lg:col-span-5">
                    <p className="font-display text-4xl leading-[1.1] tracking-[-0.02em] text-balance sm:text-[2.75rem]">
                        <Accented text={store.tagline} />
                    </p>
                    {newsletter_enabled && (
                        <div className="mt-8">
                            <p className="mb-3 text-white/80">{t('News about new products and the occasional story, by email.')}</p>
                            <NewsletterForm tone="dark" />
                        </div>
                    )}
                    {Object.keys(social).length > 0 && (
                        <ul className="mt-8 flex flex-wrap gap-x-6 gap-y-2">
                            {Object.entries(social).map(([network, href]) => (
                                <li key={network}>
                                    <a href={href} target="_blank" rel="noopener noreferrer" className={linkClass}>
                                        {SOCIAL_LABELS[network] ?? network}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <nav aria-label={t('Shop')} className="lg:col-span-2 lg:col-start-6">
                    <h2 className={headingClass}>{t('Shop')}</h2>
                    <ul className="mt-5 space-y-3">
                        {navigation.map((c) => (
                            <li key={c.slug}>
                                <Link href={to(`/shop/${c.slug}`)} className={linkClass}>
                                    {c.name}
                                </Link>
                            </li>
                        ))}
                        <li>
                            <Link href={to('/shop')} className={linkClass}>
                                {t('All products')}
                            </Link>
                        </li>
                    </ul>
                </nav>

                <nav aria-label={t('Help')} className="lg:col-span-2">
                    <h2 className={headingClass}>{t('Help')}</h2>
                    <ul className="mt-5 space-y-3">
                        <li><Link href={to('/track-order')} className={linkClass}>{t('Track an order')}</Link></li>
                        <li><Link href={to('/faq')} className={linkClass}>{t('FAQ')}</Link></li>
                        <li><Link href={to('/shipping-returns')} className={linkClass}>{t('Shipping and returns')}</Link></li>
                        <li><Link href={to('/contact')} className={linkClass}>{t('Contact')}</Link></li>
                    </ul>
                </nav>

                <div className="lg:col-span-3">
                    <h2 className={headingClass}>Aloe &amp; Soul</h2>
                    <ul className="mt-5 space-y-3">
                        <li><Link href={to('/our-story')} className={linkClass}>{t('Our story')}</Link></li>
                        <li><Link href={to('/blog')} className={linkClass}>{t('Blog')}</Link></li>
                        {contact?.email && (
                            <li><a href={`mailto:${contact.email}`} className={`${linkClass} break-words`}>{contact.email}</a></li>
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
                    </ul>
                </div>
            </div>

            <div className="container-page flex flex-col gap-5 border-t border-white/15 py-7 text-sm text-white/75 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
                    <ul className="flex flex-wrap gap-x-6 gap-y-2">
                        <li><Link href={to('/privacy-policy')} className="underline underline-offset-4 hover:text-white">{t('Privacy')}</Link></li>
                        <li><Link href={to('/terms')} className="underline underline-offset-4 hover:text-white">{t('Terms of sale')}</Link></li>
                        <li><Link href={to('/legal-notice')} className="underline underline-offset-4 hover:text-white">{t('Legal notice')}</Link></li>
                    </ul>
                    <LanguageList linkClassName="text-white/85 hover:text-white" />
                </div>
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
                    <p>
                        © {year} {store.name}
                        {contact?.locations?.length ? `. ${contact.locations.join(', ')}.` : '.'}
                    </p>
                    <p className="inline-flex items-center gap-2 self-start rounded-full bg-white px-3.5 py-1.5 font-medium text-aloe">
                        <Banknote className="size-4" strokeWidth={1.75} aria-hidden="true" />
                        {t('Payment in cash on delivery.')}
                    </p>
                </div>
            </div>
        </footer>
    );
}
