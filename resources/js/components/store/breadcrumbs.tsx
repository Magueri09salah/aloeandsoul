import { Link } from '@inertiajs/react';

import type { Breadcrumb } from '@/types';

import { cn } from '@/lib/utils';

export function Breadcrumbs({ items, className }: { items: Breadcrumb[]; className?: string }) {
    return (
        <nav aria-label="Breadcrumb" className={cn('text-sm text-stone', className)}>
            <ol className="flex flex-wrap items-center gap-x-2 gap-y-1">
                {items.map((item, i) => {
                    const last = i === items.length - 1;
                    const path = new URL(item.url).pathname;
                    return (
                        <li key={item.url} className="flex items-center gap-2">
                            {last ? (
                                <span aria-current="page" className="text-ink">
                                    {item.name}
                                </span>
                            ) : (
                                <>
                                    <Link href={path} className="hover:text-ink hover:underline hover:underline-offset-4">
                                        {item.name}
                                    </Link>
                                    <span aria-hidden="true" className="text-line">
                                        /
                                    </span>
                                </>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
