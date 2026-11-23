import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * A title whose last word is set in italic ("New *arrivals*"), the storefront's
 * heading signature. One-word titles stay upright.
 */
export function Accented({ text, className }: { text: string; className?: string }) {
    const words = text.trim().split(/\s+/);
    if (words.length < 2) return <>{text}</>;
    const last = words.pop();

    return (
        <>
            {words.join(' ')} <em className={cn('italic', className)}>{last}</em>
        </>
    );
}

export function SectionHeading({
    title,
    intro,
    link,
    className,
    as: Tag = 'h2',
    id,
}: {
    title: ReactNode;
    intro?: ReactNode;
    link?: { href: string; label: string };
    className?: string;
    as?: 'h1' | 'h2';
    id?: string;
}) {
    return (
        <div className={cn('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between', className)}>
            <div className="max-w-2xl">
                <Tag id={id} className="font-display text-display-sm text-balance">
                    {typeof title === 'string' ? <Accented text={title} /> : title}
                </Tag>
                {intro && <p className="mt-3 text-[1.05rem] text-stone">{intro}</p>}
            </div>
            {link && (
                <Link
                    href={link.href}
                    className="shrink-0 text-sm font-semibold tracking-[0.03em] uppercase underline underline-offset-4 hover:text-dune-deep"
                >
                    {link.label}
                </Link>
            )}
        </div>
    );
}
