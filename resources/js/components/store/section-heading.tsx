import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

export function SectionHeading({
    title,
    intro,
    link,
    className,
    as: Tag = 'h2',
}: {
    title: ReactNode;
    intro?: ReactNode;
    link?: { href: string; label: string };
    className?: string;
    as?: 'h1' | 'h2';
}) {
    return (
        <div className={cn('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between', className)}>
            <div className="max-w-2xl">
                <Tag className="font-display text-display-sm">{title}</Tag>
                {intro && <p className="mt-3 text-lg text-stone">{intro}</p>}
            </div>
            {link && (
                <Link href={link.href} className="shrink-0 text-[0.95rem] font-medium underline underline-offset-4 hover:text-aloe">
                    {link.label}
                </Link>
            )}
        </div>
    );
}
