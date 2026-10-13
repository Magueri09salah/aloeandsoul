import type { ImageData } from '@/types';

import { cn } from '@/lib/utils';

interface ResponsiveImageProps {
    image: ImageData;
    sizes: string;
    /** Load immediately with high priority (the page's largest image). */
    priority?: boolean;
    className?: string;
    alt?: string;
}

/** srcset/sizes with intrinsic dimensions so the layout never shifts. */
export function ResponsiveImage({ image, sizes, priority = false, className, alt }: ResponsiveImageProps) {
    return (
        <img
            src={image.src}
            srcSet={image.srcset}
            sizes={sizes}
            width={image.width}
            height={image.height}
            alt={alt ?? image.alt}
            loading={priority ? 'eager' : 'lazy'}
            decoding={priority ? 'sync' : 'async'}
            fetchPriority={priority ? 'high' : 'auto'}
            className={cn('h-full w-full object-cover', className)}
        />
    );
}
