import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { useCallback, useEffect, useRef, useState, type ReactNode } from 'react';

import type { ProductCard as ProductCardData } from '@/types';

import { Button } from '@/components/ui/button';
import { ProductCard } from '@/components/store/product-card';
import { Accented } from '@/components/store/section-heading';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface ProductRailProps {
    id: string;
    title: string;
    intro?: string | null;
    link?: { href: string; label: string };
    products: ProductCardData[];
    /** Shown above each card, e.g. the step of a routine. */
    notes?: ReactNode[];
    className?: string;
}

/**
 * A titled row of products: the title, text, link and arrows on the left, the
 * cards in a horizontal scroller on the right (native scroll-snap, so it
 * swipes on touch screens and scrolls with a trackpad).
 */
export function ProductRail({ id, title, intro, link, products, notes, className }: ProductRailProps) {
    const trackRef = useRef<HTMLUListElement>(null);
    const [edges, setEdges] = useState({ start: true, end: false });
    const { t } = useI18n();

    const update = useCallback(() => {
        const track = trackRef.current;
        if (!track) return;
        setEdges({
            start: track.scrollLeft <= 4,
            end: track.scrollLeft + track.clientWidth >= track.scrollWidth - 4,
        });
    }, []);

    useEffect(() => {
        update();
        window.addEventListener('resize', update);
        return () => window.removeEventListener('resize', update);
    }, [update]);

    const scroll = (direction: 1 | -1) => {
        const track = trackRef.current;
        const card = track?.querySelector('li');
        if (!track || !card) return;
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        track.scrollBy({
            left: direction * (card.getBoundingClientRect().width + 20),
            behavior: reduce ? 'auto' : 'smooth',
        });
    };

    const scrolls = !(edges.start && edges.end);
    const arrows = scrolls && (
        <div className="flex gap-2">
            <Button
                size="icon"
                onClick={() => scroll(-1)}
                disabled={edges.start}
                aria-label={t('Show previous products')}
                aria-controls={`${id}-track`}
            >
                <ArrowLeft aria-hidden="true" />
            </Button>
            <Button
                size="icon"
                onClick={() => scroll(1)}
                disabled={edges.end}
                aria-label={t('Show next products')}
                aria-controls={`${id}-track`}
            >
                <ArrowRight aria-hidden="true" />
            </Button>
        </div>
    );

    return (
        <section aria-labelledby={id} className={cn('py-14 lg:py-20', className)}>
            <div className="container-page grid gap-8 lg:grid-cols-[19.5rem_minmax(0,1fr)] lg:gap-10">
                <div className="flex min-w-0 flex-col lg:pt-4">
                    <h2 id={id} className="font-display text-display-sm text-balance">
                        <Accented text={title} />
                    </h2>
                    {intro && <p className="mt-3 text-[1.05rem] text-stone">{intro}</p>}
                    {link && (
                        <Button asChild wrap className="mt-8 self-start max-lg:hidden">
                            <Link href={link.href}>
                                {link.label}
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    )}
                    <div className="mt-6 max-lg:hidden">{arrows}</div>
                </div>

                <div className="min-w-0">
                    <ul
                        id={`${id}-track`}
                        ref={trackRef}
                        onScroll={update}
                        className="-m-1 no-scrollbar flex snap-x snap-mandatory gap-5 overflow-x-auto overscroll-x-contain p-1"
                    >
                        {products.map((product, i) => (
                            <li key={product.id} className="flex w-[min(68vw,17.5rem)] shrink-0 snap-start flex-col">
                                {notes?.[i]}
                                <ProductCard
                                    product={product}
                                    sizes="(min-width: 640px) 280px, 68vw"
                                    className="flex-1"
                                />
                            </li>
                        ))}
                    </ul>
                    {/* Phones: a full-width button (the row is swiped, so no arrows); tablets: button and arrows side by side. */}
                    <div
                        className={cn(
                            'mt-6 flex items-center justify-between gap-4 lg:hidden',
                            !link && 'max-sm:hidden',
                        )}
                    >
                        {link ? (
                            <Button asChild wrap className="max-sm:w-full">
                                <Link href={link.href}>
                                    {link.label}
                                    <ArrowRight aria-hidden="true" />
                                </Link>
                            </Button>
                        ) : (
                            <span />
                        )}
                        <div className="max-sm:hidden">{arrows}</div>
                    </div>
                </div>
            </div>
        </section>
    );
}
