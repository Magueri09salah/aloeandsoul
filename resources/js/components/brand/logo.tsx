import { Link } from '@inertiajs/react';

import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** The circular aloe-and-figure mark (from the client's logo) with the wordmark set in type. */
export function Logo({ className, compact = false, href = '/' }: { className?: string; compact?: boolean; href?: string }) {
    const { t } = useI18n();

    return (
        <Link href={href} className={cn('group inline-flex items-center gap-2 text-ink sm:gap-3', className)} aria-label={t('Aloe & Soul, home')}>
            <img
                src="/brand/logo-mark-96.png"
                srcSet="/brand/logo-mark-96.png 1x, /brand/logo-mark-192.png 2x"
                alt=""
                width={40}
                height={40}
                className="size-9 shrink-0 sm:size-10"
            />
            {!compact && (
                <span className="font-display text-[0.95rem] leading-none tracking-[0.1em] whitespace-nowrap text-[#55554F] sm:text-[1.15rem] sm:tracking-[0.16em]">
                    ALOE &amp; SOUL
                </span>
            )}
        </Link>
    );
}
