import { Link } from '@inertiajs/react';

import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** The client's logo: the aloe, figure and sun, with the "ALOE & SOUL" wordmark as drawn in it. */
export function Logo({
    className,
    wordmarkClassName,
    compact = false,
    href = '/',
}: {
    className?: string;
    /** Extra classes for the name beside the mark, e.g. to hide it on the narrowest phones. */
    wordmarkClassName?: string;
    compact?: boolean;
    href?: string;
}) {
    const { t } = useI18n();

    return (
        <Link
            href={href}
            className={cn('group inline-flex items-center gap-1.5 sm:gap-3', className)}
            aria-label={t('Aloe & Soul, home')}
        >
            <img
                src="/brand/logo-mark-96.png"
                srcSet="/brand/logo-mark-96.png 1x, /brand/logo-mark-192.png 2x"
                alt=""
                width={109}
                height={96}
                className="h-8 w-auto shrink-0 sm:h-11"
            />
            {!compact && (
                <img
                    src="/brand/logo-wordmark-40.png"
                    srcSet="/brand/logo-wordmark-40.png 1x, /brand/logo-wordmark-80.png 2x"
                    alt=""
                    width={362}
                    height={40}
                    className={cn('h-2.5 w-auto sm:h-3.5', wordmarkClassName)}
                />
            )}
        </Link>
    );
}
