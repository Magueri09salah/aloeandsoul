import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import type { Paginated } from '@/types';

import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export function PageHeader({
    title,
    description,
    actions,
    back,
}: {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    back?: { href: string; label: string };
}) {
    return (
        <div className="mb-8">
            <Head title={`${title} — Admin`} />
            {back && (
                <Link href={back.href} className="mb-3 inline-block text-sm text-stone hover:text-ink">
                    ← {back.label}
                </Link>
            )}
            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 className="font-display text-3xl sm:text-4xl">{title}</h1>
                    {description && <div className="mt-2 max-w-2xl text-stone">{description}</div>}
                </div>
                {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
            </div>
        </div>
    );
}

export function Panel({ title, description, actions, children, className, padded = true }: {
    title?: ReactNode;
    description?: ReactNode;
    actions?: ReactNode;
    children: ReactNode;
    className?: string;
    padded?: boolean;
}) {
    return (
        <section className={cn('rounded-lg border border-line bg-white', className)}>
            {(title || actions) && (
                <header className="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4">
                    <div>
                        {title && <h2 className="font-medium">{title}</h2>}
                        {description && <p className="mt-0.5 text-sm text-stone">{description}</p>}
                    </div>
                    {actions}
                </header>
            )}
            <div className={cn(padded && 'p-5')}>{children}</div>
        </section>
    );
}

/** Horizontally scrollable table on small screens. */
export function Table({ children, className }: { children: ReactNode; className?: string }) {
    return (
        <div className="overflow-x-auto">
            <table className={cn('w-full text-left text-sm', className)}>{children}</table>
        </div>
    );
}

export const th = 'px-4 py-3 text-xs font-medium text-stone whitespace-nowrap border-b border-line bg-panel/60';
export const td = 'px-4 py-3 border-b border-line align-middle';

export function EmptyState({ title, children }: { title: string; children?: ReactNode }) {
    return (
        <div className="px-6 py-14 text-center">
            <p className="font-medium">{title}</p>
            {children && <div className="mt-2 text-sm text-stone">{children}</div>}
        </div>
    );
}

const ORDER_TONES: Record<string, 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'dark'> = {
    PENDING_CONFIRMATION: 'warning',
    CONFIRMED: 'info',
    PROCESSING: 'info',
    READY_TO_SHIP: 'info',
    SHIPPED: 'dark',
    DELIVERED: 'success',
    CANCELLED: 'neutral',
    FAILED_DELIVERY: 'danger',
    RETURNED: 'danger',
};

const PAYMENT_TONES: Record<string, 'neutral' | 'success' | 'warning' | 'danger'> = {
    pending: 'neutral',
    collected: 'success',
    not_collected: 'warning',
    refunded: 'danger',
};

export function OrderStatusBadge({ status, label }: { status: string; label: string }) {
    return <Badge tone={ORDER_TONES[status] ?? 'neutral'}>{label}</Badge>;
}

export function PaymentBadge({ status, label }: { status: string; label: string }) {
    return <Badge tone={PAYMENT_TONES[status] ?? 'neutral'}>{label}</Badge>;
}

export function ProductStatusBadge({ status, label }: { status: string; label: string }) {
    return <Badge tone={status === 'published' ? 'success' : status === 'draft' ? 'warning' : 'neutral'}>{label}</Badge>;
}

export function Pagination<T>({ page }: { page: Paginated<T> }) {
    if (page.last_page <= 1) return null;

    return (
        <nav aria-label="Pages" className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
            <p className="text-stone">
                {page.from}–{page.to} of {page.total}
            </p>
            <div className="flex flex-wrap gap-1">
                {page.links.map((link, i) =>
                    link.url ? (
                        <Link
                            key={i}
                            href={link.url}
                            preserveScroll
                            aria-current={link.active ? 'page' : undefined}
                            className={cn('inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2.5 hover:bg-panel', link.active && 'bg-ink text-ivory hover:bg-ink')}
                        >
                            {cleanLabel(link.label)}
                        </Link>
                    ) : (
                        <span key={i} className="inline-flex h-8 min-w-8 items-center justify-center px-2.5 text-stone/50">
                            {cleanLabel(link.label)}
                        </span>
                    ),
                )}
            </div>
        </nav>
    );
}

function cleanLabel(label: string): string {
    return label.replace('&laquo; Previous', '‹ Previous').replace('Next &raquo;', 'Next ›').replace(/&[a-z]+;/g, '');
}

export function Stat({ label, value, hint, href }: { label: string; value: ReactNode; hint?: ReactNode; href?: string }) {
    const body = (
        <>
            <p className="text-sm text-stone">{label}</p>
            <p className="mt-2 font-display text-3xl tabular-nums">{value}</p>
            {hint && <p className="mt-1 text-xs text-stone">{hint}</p>}
        </>
    );
    return href ? (
        <Link href={href} className="block rounded-lg border border-line bg-white p-5 transition-colors hover:border-stone/50">
            {body}
        </Link>
    ) : (
        <div className="rounded-lg border border-line bg-white p-5">{body}</div>
    );
}
