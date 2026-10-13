import { Link } from '@inertiajs/react';

import { cn } from '@/lib/utils';

/** The circular aloe-and-figure mark (from the client's logo) with the wordmark set in type. */
export function Logo({ className, compact = false, href = '/' }: { className?: string; compact?: boolean; href?: string }) {
    return (
        <Link href={href} className={cn('group inline-flex items-center gap-3 text-ink', className)} aria-label="Aloe & Soul, home">
            <img
                src="/brand/logo-mark-96.png"
                srcSet="/brand/logo-mark-96.png 1x, /brand/logo-mark-192.png 2x"
                alt=""
                width={40}
                height={40}
                className="size-10 shrink-0"
            />
            {!compact && (
                <span className="font-display text-[1.15rem] leading-none tracking-[0.16em] whitespace-nowrap text-[#55554F]">
                    ALOE &amp; SOUL
                </span>
            )}
        </Link>
    );
}
