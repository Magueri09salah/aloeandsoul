import { Head, Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

import { FlashToaster } from '@/components/ui/flash-toaster';
import { CartUiProvider } from '@/components/store/cart-context';
import { CartDrawer } from '@/components/store/cart-drawer';
import { SiteFooter } from '@/components/store/site-footer';
import { SiteHeader } from '@/components/store/site-header';
import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';

/** Persistent storefront layout: header, footer, cart drawer survive navigations. */
export default function StoreLayout({ children }: { children: ReactNode }) {
    const { announcement, seo } = useShared();
    const { t } = useI18n();

    return (
        <CartUiProvider>
            {seo && (
                <Head>
                    <title>{seo.title}</title>
                    <meta head-key="description" name="description" content={seo.description} />
                    <meta head-key="robots" name="robots" content={seo.robots} />
                    {seo.canonical && <link head-key="canonical" rel="canonical" href={seo.canonical} />}
                </Head>
            )}
            <a
                href="#main"
                className="sr-only z-50 rounded-full bg-ink px-4 py-2 text-ivory focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
            >
                {t('Skip to content')}
            </a>
            {announcement && (
                <div className="bg-aloe text-ivory">
                    <p className="container-page py-2.5 text-center text-sm">
                        {announcement.text}
                        {announcement.link_url && announcement.link_label && (
                            <>
                                {' '}
                                {announcement.link_url.startsWith('/') ? (
                                    <Link href={announcement.link_url} className="underline underline-offset-4 hover:text-white">
                                        {announcement.link_label}
                                    </Link>
                                ) : (
                                    <a href={announcement.link_url} rel="noopener noreferrer" className="underline underline-offset-4 hover:text-white">
                                        {announcement.link_label}
                                    </a>
                                )}
                            </>
                        )}
                    </p>
                </div>
            )}
            <SiteHeader />
            <main id="main" tabIndex={-1} className="outline-none">
                {children}
            </main>
            <SiteFooter />
            <CartDrawer />
            <FlashToaster />
        </CartUiProvider>
    );
}
