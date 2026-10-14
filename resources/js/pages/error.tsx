import { Link, usePage } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { useI18n } from '@/lib/i18n';

export default function ErrorPage({ status, request_id }: { status: number; request_id?: string | null }) {
    const { url } = usePage();
    const { t, to } = useI18n();
    const isAdmin = url.startsWith('/admin');

    const messages: Record<number, { title: string; text: string }> = {
        403: { title: t('You don’t have access to this page.'), text: t('If you think you should, ask the shop owner to check your account.') },
        404: { title: t('This page doesn’t exist.'), text: t('It may have moved, or the address may be mistyped.') },
        410: { title: t('This page has been removed.'), text: t('The item is no longer available.') },
        429: { title: t('Too many requests.'), text: t('Please wait a minute and try again.') },
        500: {
            title: t('Something went wrong on our side.'),
            text: t('Please try again in a moment. If it keeps happening, contact us and quote the reference below.'),
        },
        503: { title: t('We’ll be right back.'), text: t('The shop is being updated. Please try again in a few minutes.') },
    };
    const message = messages[status] ?? messages[500]!;

    return (
        <section className="container-page max-w-2xl py-24 text-center">
            <p className="font-display text-6xl text-dune">{status}</p>
            <h1 className="mt-4 font-display text-display-sm">{message.title}</h1>
            <p className="mt-4 text-lg text-stone">{message.text}</p>
            {status >= 500 && request_id && <p className="mt-4 text-sm text-stone">{t('Reference: :id', { id: request_id })}</p>}
            <div className="mt-10 flex flex-wrap justify-center gap-3">
                <Button asChild>
                    <Link href={isAdmin ? '/admin' : to('/')}>{isAdmin ? t('Back to the dashboard') : t('Go to the home page')}</Link>
                </Button>
                {!isAdmin && (
                    <Button asChild variant="secondary">
                        <Link href={to('/shop')}>{t('Browse the shop')}</Link>
                    </Button>
                )}
            </div>
        </section>
    );
}
