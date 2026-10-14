import { Link, router, usePage } from '@inertiajs/react';
import { SlidersHorizontal } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import type { Breadcrumb, Motif as MotifName, ProductCard as ProductCardData } from '@/types';

import { Motif } from '@/components/brand/motif';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input, Label, Select } from '@/components/ui/input';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { Breadcrumbs } from '@/components/store/breadcrumbs';
import { ProductCard } from '@/components/store/product-card';
import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface Filters {
    q: string | null;
    availability: 'in_stock' | null;
    on_sale: boolean;
    min_price: string | null;
    max_price: string | null;
    sort: string;
}

interface ShopProps {
    category: { name: string; slug: string; tagline: string | null; description: string | null; tint: string; motif: MotifName } | null;
    categories: { name: string; slug: string; tint: string; product_count: number }[];
    products: {
        data: ProductCardData[];
        meta: { current_page: number; last_page: number; total: number; from: number | null; to: number | null };
        links: { prev: string | null; next: string | null; pages: { page: number; url: string }[] };
    };
    filters: Filters;
    breadcrumbs: Breadcrumb[];
}

export default function Shop({ category, categories, products, filters, breadcrumbs }: ShopProps) {
    const { url } = usePage();
    const { t, tc, to } = useI18n();
    const path = url.split('?')[0] ?? to('/shop');
    const sorts = [
        { value: 'featured', label: t('Featured') },
        { value: 'newest', label: t('Newest') },
        { value: 'price_asc', label: t('Price, low to high') },
        { value: 'price_desc', label: t('Price, high to low') },
        { value: 'name', label: t('Name, A to Z') },
    ];
    const [sheetOpen, setSheetOpen] = useState(false);

    const apply = (next: Partial<Filters>) => {
        const merged = { ...filters, ...next };
        const query: Record<string, string> = {};
        if (merged.q) query.q = merged.q;
        if (merged.availability) query.availability = merged.availability;
        if (merged.on_sale) query.on_sale = '1';
        if (merged.min_price) query.min_price = merged.min_price;
        if (merged.max_price) query.max_price = merged.max_price;
        if (merged.sort && merged.sort !== 'featured') query.sort = merged.sort;
        router.get(path, query, { preserveScroll: true, preserveState: true, replace: true });
    };

    const activeCount = [filters.q, filters.availability, filters.on_sale || null, filters.min_price, filters.max_price].filter(Boolean).length;

    return (
        <>
            <header className="relative overflow-hidden" style={{ backgroundColor: category?.tint ?? 'var(--color-sand)' }}>
                <div className="container-page relative py-10 lg:py-16">
                    <Breadcrumbs items={breadcrumbs} />
                    <div className="mt-6 max-w-2xl">
                        <h1 className="font-display text-display">{category?.name ?? t('All products')}</h1>
                        {(category?.description ?? category?.tagline) && (
                            <p className="mt-4 text-lg text-ink/80">{category?.description ?? category?.tagline}</p>
                        )}
                        {!category && <p className="mt-4 text-lg text-ink/80">{t('Wellness, beauty, hygiene, fitness and nutrition, chosen by the founder.')}</p>}
                    </div>
                    <div className="pointer-events-none absolute top-1/2 right-6 hidden w-56 -translate-y-1/2 text-dune/70 md:block lg:right-16 lg:w-72">
                        <Motif name={category?.motif ?? 'circle'} />
                    </div>
                </div>
            </header>

            <nav aria-label={t('Categories')} className="border-b border-line">
                <ul className="container-page flex gap-2 overflow-x-auto py-4 [scrollbar-width:none]">
                    <li>
                        <CategoryPill href={to('/shop')} active={!category}>
                            {t('All')}
                        </CategoryPill>
                    </li>
                    {categories.map((c) => (
                        <li key={c.slug}>
                            <CategoryPill href={to(`/shop/${c.slug}`)} active={category?.slug === c.slug} tint={c.tint}>
                                {c.name}
                            </CategoryPill>
                        </li>
                    ))}
                </ul>
            </nav>

            <div className="container-page grid gap-10 py-10 lg:grid-cols-[15rem_1fr] lg:gap-12">
                <aside className="hidden lg:block" aria-label={t('Filters')}>
                    <FilterPanel filters={filters} onApply={apply} />
                </aside>

                <section aria-labelledby="results-heading">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4">
                        <h2 id="results-heading" className="text-stone" aria-live="polite">
                            {filters.q ? (
                                <>
                                    {tc(':count result for|:count results for', products.meta.total)} <span className="text-ink">“{filters.q}”</span>
                                </>
                            ) : (
                                tc(':count product|:count products', products.meta.total)
                            )}
                        </h2>
                        <div className="flex items-center gap-2">
                            <Button variant="secondary" size="sm" className="lg:hidden" onClick={() => setSheetOpen(true)}>
                                <SlidersHorizontal aria-hidden="true" />
                                {t('Filter')}{activeCount > 0 ? ` (${activeCount})` : ''}
                            </Button>
                            <label htmlFor="sort" className="sr-only">
                                {t('Sort by')}
                            </label>
                            <Select id="sort" value={filters.sort} onChange={(e) => apply({ sort: e.target.value })} className="h-9 w-auto rounded-full text-sm">
                                {sorts.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.label}
                                    </option>
                                ))}
                            </Select>
                        </div>
                    </div>

                    {products.data.length === 0 ? (
                        <div className="py-20 text-center">
                            <p className="font-display text-3xl">{t('No products match.')}</p>
                            <p className="mt-3 text-stone">
                                {activeCount > 0 ? t('Try removing a filter or searching for something else.') : t('New products are on their way. Please come back soon.')}
                            </p>
                            {activeCount > 0 && (
                                <Button variant="secondary" className="mt-6" onClick={() => router.get(path)}>
                                    {t('Clear filters')}
                                </Button>
                            )}
                        </div>
                    ) : (
                        <div className="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-3 sm:gap-x-6">
                            {products.data.map((p, i) => (
                                <ProductCard key={p.id} product={p} priority={i < 2} sizes="(min-width: 1024px) 22vw, (min-width: 640px) 33vw, 50vw" />
                            ))}
                        </div>
                    )}

                    {products.meta.last_page > 1 && (
                        <nav aria-label={t('Pages')} className="mt-14 flex flex-wrap items-center justify-center gap-1">
                            {products.links.prev && (
                                <Link href={products.links.prev} preserveScroll={false} className="mr-2 rounded-full px-4 py-2 hover:bg-sand" rel="prev">
                                    {t('Previous')}
                                </Link>
                            )}
                            {products.links.pages.map((p) => (
                                <Link
                                    key={p.page}
                                    href={p.url}
                                    aria-current={p.page === products.meta.current_page ? 'page' : undefined}
                                    className={cn(
                                        'inline-flex size-10 items-center justify-center rounded-full tabular-nums hover:bg-sand',
                                        p.page === products.meta.current_page && 'bg-ink text-ivory hover:bg-ink',
                                    )}
                                >
                                    {p.page}
                                </Link>
                            ))}
                            {products.links.next && (
                                <Link href={products.links.next} className="ml-2 rounded-full px-4 py-2 hover:bg-sand" rel="next">
                                    {t('Next')}
                                </Link>
                            )}
                        </nav>
                    )}
                </section>
            </div>

            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                <SheetContent side="bottom" title={t('Filter')}>
                    <div className="overflow-y-auto px-5 pt-4 pb-8">
                        <FilterPanel
                            filters={filters}
                            onApply={(next) => {
                                apply(next);
                                setSheetOpen(false);
                            }}
                            instant={false}
                        />
                    </div>
                </SheetContent>
            </Sheet>
        </>
    );
}

function CategoryPill({ href, active, tint, children }: { href: string; active: boolean; tint?: string; children: React.ReactNode }) {
    return (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={cn(
                'inline-flex h-10 items-center gap-2 rounded-full border px-4 text-sm whitespace-nowrap transition-colors',
                active ? 'border-ink bg-ink text-ivory' : 'border-line bg-white hover:border-ink',
            )}
        >
            {tint && <span className="size-2.5 rounded-full" style={{ backgroundColor: tint }} aria-hidden="true" />}
            {children}
        </Link>
    );
}

function FilterPanel({ filters, onApply, instant = true }: { filters: Filters; onApply: (next: Partial<Filters>) => void; instant?: boolean }) {
    const { store } = useShared();
    const { t } = useI18n();
    const [draft, setDraft] = useState({
        q: filters.q ?? '',
        min_price: filters.min_price ?? '',
        max_price: filters.max_price ?? '',
        availability: filters.availability,
        on_sale: filters.on_sale,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onApply({
            q: draft.q.trim() || null,
            min_price: draft.min_price.trim() || null,
            max_price: draft.max_price.trim() || null,
            availability: draft.availability,
            on_sale: draft.on_sale,
        });
    };

    const toggle = (next: Partial<typeof draft>) => {
        setDraft((d) => ({ ...d, ...next }));
        if (instant) onApply(next as Partial<Filters>);
    };

    return (
        <form onSubmit={submit} className="space-y-8">
            <div className="space-y-2">
                <Label htmlFor="filter-q">{t('Search')}</Label>
                <Input id="filter-q" type="search" value={draft.q} maxLength={100} onChange={(e) => setDraft({ ...draft, q: e.target.value })} placeholder={t('Product name')} />
            </div>

            <fieldset className="space-y-3">
                <legend className="mb-3 text-sm font-medium">{t('Availability')}</legend>
                <div className="flex items-center gap-3">
                    <Checkbox
                        id="filter-stock"
                        checked={draft.availability === 'in_stock'}
                        onCheckedChange={(v) => toggle({ availability: v === true ? 'in_stock' : null })}
                    />
                    <Label htmlFor="filter-stock" className="font-normal">
                        {t('In stock only')}
                    </Label>
                </div>
                <div className="flex items-center gap-3">
                    <Checkbox id="filter-sale" checked={draft.on_sale} onCheckedChange={(v) => toggle({ on_sale: v === true })} />
                    <Label htmlFor="filter-sale" className="font-normal">
                        {t('On sale')}
                    </Label>
                </div>
            </fieldset>

            <fieldset>
                <legend className="mb-3 text-sm font-medium">{t('Price (:currency)', { currency: store.currency })}</legend>
                <div className="flex items-center gap-2">
                    <label htmlFor="filter-min" className="sr-only">
                        {t('Minimum price')}
                    </label>
                    <Input id="filter-min" inputMode="decimal" placeholder={t('Min')} value={draft.min_price} onChange={(e) => setDraft({ ...draft, min_price: e.target.value })} />
                    <span aria-hidden="true" className="text-stone">
                        –
                    </span>
                    <label htmlFor="filter-max" className="sr-only">
                        {t('Maximum price')}
                    </label>
                    <Input id="filter-max" inputMode="decimal" placeholder={t('Max')} value={draft.max_price} onChange={(e) => setDraft({ ...draft, max_price: e.target.value })} />
                </div>
            </fieldset>

            <Button type="submit" variant="secondary" className="w-full">
                {t('Apply')}
            </Button>
        </form>
    );
}
