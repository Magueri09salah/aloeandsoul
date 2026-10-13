import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useRef, useState, type KeyboardEvent, type ReactNode } from 'react';

import type { ImageData } from '@/types';

import { Button } from '@/components/ui/button';
import { ResponsiveImage } from '@/components/ui/responsive-image';
import { cn } from '@/lib/utils';

interface ProductGalleryProps {
    images: ImageData[];
    /** Category colour behind transparent packshots. */
    tint: string;
    label: string;
    /** Shown when the product has no images yet. */
    placeholder: ReactNode;
}

/**
 * Product photographs as a swipeable carousel: native scroll-snap for touch
 * and trackpads, arrow buttons, keyboard arrows and thumbnails. Images are
 * shown whole (contain) in a square frame, so packshots, photos and
 * nutrition tables are never cropped.
 */
export function ProductGallery({ images, tint, label, placeholder }: ProductGalleryProps) {
    const trackRef = useRef<HTMLDivElement>(null);
    // While a button scrolls the track, ignore the intermediate scroll positions.
    const targetRef = useRef<number | null>(null);
    const releaseRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const [index, setIndex] = useState(0);
    const count = images.length;

    useEffect(
        () => () => {
            if (releaseRef.current) clearTimeout(releaseRef.current);
        },
        [],
    );

    const show = (next: number) => {
        const i = Math.max(0, Math.min(count - 1, next));
        setIndex(i);
        const track = trackRef.current;
        if (!track) return;
        targetRef.current = i;
        if (releaseRef.current) clearTimeout(releaseRef.current);
        releaseRef.current = setTimeout(() => {
            targetRef.current = null;
        }, 800);
        track.scrollLeft = i * track.clientWidth;
    };

    const onScroll = () => {
        const track = trackRef.current;
        if (!track || track.clientWidth === 0) return;
        const current = Math.round(track.scrollLeft / track.clientWidth);
        if (targetRef.current !== null) {
            if (current === targetRef.current) targetRef.current = null;
            return;
        }
        setIndex(Math.max(0, Math.min(count - 1, current)));
    };

    const onKeyDown = (e: KeyboardEvent) => {
        const targets: Record<string, number> = { ArrowLeft: index - 1, ArrowRight: index + 1, Home: 0, End: count - 1 };
        const next = targets[e.key];
        if (next === undefined) return;
        e.preventDefault();
        show(next);
    };

    if (count === 0) {
        return (
            <div className="aspect-square overflow-hidden" style={{ backgroundColor: tint }}>
                {placeholder}
            </div>
        );
    }

    return (
        <section aria-roledescription="carousel" aria-label={label}>
            <div className="relative">
                <div
                    ref={trackRef}
                    onScroll={onScroll}
                    onKeyDown={count > 1 ? onKeyDown : undefined}
                    tabIndex={count > 1 ? 0 : undefined}
                    aria-label={count > 1 ? 'Use the arrow keys to see the other images' : undefined}
                    className="flex aspect-square snap-x snap-mandatory overflow-x-auto overscroll-x-contain [scrollbar-width:none] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-aloe motion-safe:scroll-smooth [&::-webkit-scrollbar]:hidden"
                    style={{ backgroundColor: tint }}
                >
                    {images.map((image, i) => (
                        <div
                            key={image.src}
                            role="group"
                            aria-roledescription="slide"
                            aria-label={`${i + 1} of ${count}`}
                            className="h-full w-full shrink-0 snap-center snap-always"
                        >
                            <ResponsiveImage image={image} sizes="(min-width: 1024px) 55vw, 100vw" priority={i === 0} className="object-contain" />
                        </div>
                    ))}
                </div>

                {count > 1 && (
                    <div className="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-between gap-3 p-3">
                        <p className="pointer-events-auto rounded-full bg-ivory/90 px-3 py-1 text-sm tabular-nums" aria-live="polite" aria-atomic="true">
                            <span aria-hidden="true">
                                {index + 1} / {count}
                            </span>
                            <span className="sr-only">
                                Image {index + 1} of {count}
                            </span>
                        </p>
                        <div className="pointer-events-auto flex gap-2">
                            <Button variant="quiet" size="icon-sm" className="bg-ivory/90 hover:bg-ivory" onClick={() => show(index - 1)} disabled={index === 0} aria-label="Previous image">
                                <ChevronLeft aria-hidden="true" />
                            </Button>
                            <Button variant="quiet" size="icon-sm" className="bg-ivory/90 hover:bg-ivory" onClick={() => show(index + 1)} disabled={index === count - 1} aria-label="Next image">
                                <ChevronRight aria-hidden="true" />
                            </Button>
                        </div>
                    </div>
                )}
            </div>

            {count > 1 && (
                <ul className="mt-3 flex flex-wrap gap-2" aria-label="Choose an image">
                    {images.map((image, i) => (
                        <li key={image.src}>
                            <button
                                type="button"
                                onClick={() => show(i)}
                                className={cn(
                                    'block size-16 overflow-hidden ring-offset-2 ring-offset-ivory transition sm:size-20',
                                    i === index ? 'ring-2 ring-ink' : 'opacity-75 hover:opacity-100',
                                )}
                                style={{ backgroundColor: tint }}
                                aria-label={`Show image ${i + 1} of ${count}`}
                                aria-current={i === index ? 'true' : undefined}
                            >
                                <ResponsiveImage image={image} sizes="80px" alt="" className="object-contain" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
