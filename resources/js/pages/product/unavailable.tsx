import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { useI18n } from '@/lib/i18n';

export default function ProductUnavailable({ name, category }: { name: string; category: { name: string; slug: string } }) {
    const { t, to } = useI18n();

    return (
        <section className="container-page max-w-2xl py-24 text-center">
            <h1 className="font-display text-display-sm">{t(':name is no longer available', { name })}</h1>
            <p className="mt-4 text-lg text-stone">{t('This product has been discontinued. You may find something similar in the same collection.')}</p>
            <div className="mt-8 flex flex-wrap justify-center gap-3">
                <Button asChild>
                    <Link href={to(`/shop/${category.slug}`)}>{t('Browse :category', { category: category.name })}</Link>
                </Button>
                <Button asChild variant="secondary">
                    <Link href={to('/contact')}>{t('Ask us')}</Link>
                </Button>
            </div>
        </section>
    );
}
