import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, SlidersHorizontal } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';

import type { Breadcrumb, Motif as MotifName, ProductCard as ProductCardData } from '@/types';

import { Motif } from '@/components/brand/motif';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input, Label } from '@/components/ui/input';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { Breadcrumbs } from '@/components/store/breadcrumbs';
import { ProductCard } from '@/components/store/product-card';
import { Accented } from '@/components/store/section-heading';
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
    category: {
        name: string;
        slug: string;
        tagline: string | null;
        description: string | null;
        tint: string;
        motif: MotifName;
    } | null;
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

    const activeCount = [
        filters.q,
        filters.availability,
        filters.on_sale || null,
        filters.min_price,
        filters.max_price,
    ].filter(Boolean).length;
    const description =
        category?.description ??
        category?.tagline ??
        (category ? null : t('Wellness, beauty, hygiene, fitness and nutrition, chosen by the founder.'));
    const resultCount: ReactNode = filters.q ? (
        <>
            {tc(':count result for|:count results for', products.meta.total)}{' '}
            <span className="text-ink">“{filters.q}”</span>
        </>
    ) : (
        tc(':count product|:count products', products.meta.total)
    );

    return (
        <>
            {/* Banner in the category's own colour (the colour of its circle on the home page). */}
            <div
                className="relative isolate overflow-hidden"
                style={{ backgroundColor: category?.tint ?? 'var(--color-sand)' }}
            >
                <div className="pointer-events-none absolute top-1/2 right-[3%] -z-10 hidden w-64 -translate-y-1/2 text-dune/40 xl:block">
                    <Motif name={category?.motif ?? 'circle'} animated />
                </div>
                <header className="container-page pt-6 pb-8 text-center lg:pt-8 lg:pb-10">
                    <Breadcrumbs items={breadcrumbs} className="text-ink/85 [&_ol]:justify-center" />
                    <h1 className="mt-10 font-display text-display text-balance lg:mt-14">
                        <Accented text={category?.name ?? t('All products')} />
                    </h1>
                    {description && <p className="mx-auto mt-4 max-w-2xl text-[1.05rem] text-ink/85">{description}</p>}
                </header>

                {/* Categories as tabs. */}
                <nav aria-label={t('Categories')} className="container-page">
                    <ul className="no-scrollbar flex gap-x-7 overflow-x-auto sm:justify-center">
                        <li>
                            <CategoryTab href={to('/shop')} active={!category}>
                                {t('All')}
                            </CategoryTab>
                        </li>
                        {categories.map((c) => (
                            <li key={c.slug}>
                                <CategoryTab href={to(`/shop/${c.slug}`)} active={category?.slug === c.slug}>
                                    {c.name}
                                </CategoryTab>
                            </li>
                        ))}
                    </ul>
                </nav>
            </div>

            {/* Toolbar: filters, product count and sort; stays under the header while scrolling. */}
            <div className="sticky top-[4.5rem] z-30 border-b border-line bg-ivory/95 backdrop-blur supports-[backdrop-filter]:bg-ivory/85 lg:top-20">
                <div className="container-page grid min-h-14 grid-cols-[auto_minmax(0,1fr)_auto] items-stretch">
                    <button
                        type="button"
                        onClick={() => setSheetOpen(true)}
                        className="-ml-1 flex items-center gap-2.5 border-r border-line pr-4 pl-1 text-sm font-semibold tracking-[0.03em] uppercase hover:text-dune-deep sm:pr-8"
                    >
                        <SlidersHorizontal className="size-4" aria-hidden="true" />
                        {t('Filters')}
                        {activeCount > 0 && (
                            <span className="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-dune-deep px-1.5 text-[0.7rem] text-white tabular-nums">
                                {activeCount}
                            </span>
                        )}
                    </button>
                    <h2
                        id="results-heading"
                        className="flex items-center justify-center px-3 text-center text-sm text-stone"
                        aria-live="polite"
                    >
                        {/* On phones there is no room between the two controls: the count is shown above the products instead (and still read out from here). */}
                        <span className="truncate max-sm:sr-only">{resultCount}</span>
                    </h2>
                    <div className="relative flex items-center border-l border-line pl-4 sm:pl-8">
                        <label htmlFor="sort" className="mr-2 hidden text-sm text-stone md:inline">
                            {t('Sort by')}
                        </label>
                        <select
                            id="sort"
                            value={filters.sort}
                            onChange={(e) => apply({ sort: e.target.value })}
                            className="h-10 max-w-[9.5rem] cursor-pointer appearance-none truncate rounded-full bg-transparent pr-6 text-sm font-semibold tracking-[0.03em] uppercase focus-visible:outline-2 focus-visible:outline-aloe sm:max-w-none"
                        >
                            {sorts.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.label}
                                </option>
                            ))}
                        </select>
                        <ChevronDown
                            className="pointer-events-none absolute right-0 size-4 text-stone"
                            aria-hidden="true"
                        />
                    </div>
                </div>
            </div>

            <section aria-labelledby="results-heading" className="container-page pt-10">
                {products.data.length === 0 ? (
                    <div className="py-20 text-center">
                        <p className="font-display text-3xl">{t('No products match.')}</p>
                        <p className="mt-3 text-stone">
                            {activeCount > 0
                                ? t('Try removing a filter or searching for something else.')
                                : t('New products are on their way. Please come back soon.')}
                        </p>
                        {activeCount > 0 && (
                            <Button variant="secondary" className="mt-6" onClick={() => router.get(path)}>
                                {t('Clear filters')}
                            </Button>
                        )}
                    </div>
                ) : (
                    <>
                        <p aria-hidden="true" className="-mt-4 mb-6 text-sm text-stone sm:hidden">
                            {resultCount}
                        </p>
                        <div className="grid grid-cols-2 gap-x-4 gap-y-12 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4">
                            {products.data.map((p, i) => (
                                <ProductCard
                                    key={p.id}
                                    product={p}
                                    priority={i < 2}
                                    sizes="(min-width: 1024px) 22vw, (min-width: 640px) 31vw, 48vw"
                                />
                            ))}
                        </div>
                    </>
                )}

                {products.meta.last_page > 1 && (
                    <nav aria-label={t('Pages')} className="mt-16 flex flex-wrap items-center justify-center gap-2">
                        {products.links.prev && (
                            <Link
                                href={products.links.prev}
                                preserveScroll={false}
                                className="mr-2 rounded-full px-4 py-2 text-sm font-semibold tracking-[0.03em] uppercase hover:bg-sand"
                                rel="prev"
                            >
                                {t('Previous')}
                            </Link>
                        )}
                        {products.links.pages.map((p) => (
                            <Link
                                key={p.page}
                                href={p.url}
                                aria-current={p.page === products.meta.current_page ? 'page' : undefined}
                                className={cn(
                                    'inline-flex size-11 items-center justify-center rounded-full border border-line tabular-nums hover:border-ink',
                                    p.page === products.meta.current_page &&
                                        'border-dune-deep bg-dune-deep text-white hover:border-dune-deep',
                                )}
                            >
                                {p.page}
                            </Link>
                        ))}
                        {products.links.next && (
                            <Link
                                href={products.links.next}
                                className="ml-2 rounded-full px-4 py-2 text-sm font-semibold tracking-[0.03em] uppercase hover:bg-sand"
                                rel="next"
                            >
                                {t('Next')}
                            </Link>
                        )}
                    </nav>
                )}
            </section>

            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                <SheetContent side="left" title={activeCount > 0 ? `${t('Filters')} (${activeCount})` : t('Filters')}>
                    <FilterPanel
                        filters={filters}
                        onApply={(next) => {
                            apply(next);
                            setSheetOpen(false);
                        }}
                        onClear={
                            activeCount > 0
                                ? () => {
                                      setSheetOpen(false);
                                      router.get(path, filters.sort !== 'featured' ? { sort: filters.sort } : {});
                                  }
                                : undefined
                        }
                    />
                </SheetContent>
            </Sheet>
        </>
    );
}

function CategoryTab({ href, active, children }: { href: string; active: boolean; children: ReactNode }) {
    return (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={cn(
                'inline-flex h-11 items-center border-b-2 text-[0.95rem] whitespace-nowrap transition-colors',
                active ? 'border-dune-deep text-ink' : 'border-transparent text-ink/85 hover:text-ink',
            )}
        >
            {children}
        </Link>
    );
}

/** One collapsible group of the filter panel. */
function FilterGroup({ title, children }: { title: string; children: ReactNode }) {
    return (
        <details open className="group border-b border-line">
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 py-4 font-medium [&::-webkit-details-marker]:hidden">
                {title}
                <ChevronDown
                    className="size-4 text-stone transition-transform group-open:rotate-180"
                    aria-hidden="true"
                />
            </summary>
            <div className="pb-5">{children}</div>
        </details>
    );
}

function FilterPanel({
    filters,
    onApply,
    onClear,
}: {
    filters: Filters;
    onApply: (next: Partial<Filters>) => void;
    onClear?: () => void;
}) {
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

    return (
        <form onSubmit={submit} className="flex min-h-0 flex-1 flex-col">
            <div className="flex-1 overflow-y-auto px-5">
                <FilterGroup title={t('Search')}>
                    <Label htmlFor="filter-q" className="sr-only">
                        {t('Search')}
                    </Label>
                    <Input
                        id="filter-q"
                        type="search"
                        value={draft.q}
                        maxLength={100}
                        onChange={(e) => setDraft({ ...draft, q: e.target.value })}
                        placeholder={t('Product name')}
                    />
                </FilterGroup>

                <FilterGroup title={t('Availability')}>
                    <div className="space-y-3">
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="filter-stock"
                                checked={draft.availability === 'in_stock'}
                                onCheckedChange={(v) =>
                                    setDraft({ ...draft, availability: v === true ? 'in_stock' : null })
                                }
                            />
                            <Label htmlFor="filter-stock" className="font-normal">
                                {t('In stock only')}
                            </Label>
                        </div>
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="filter-sale"
                                checked={draft.on_sale}
                                onCheckedChange={(v) => setDraft({ ...draft, on_sale: v === true })}
                            />
                            <Label htmlFor="filter-sale" className="font-normal">
                                {t('On sale')}
                            </Label>
                        </div>
                    </div>
                </FilterGroup>

                <FilterGroup title={t('Price (:currency)', { currency: store.currency })}>
                    <div className="flex items-center gap-2">
                        <label htmlFor="filter-min" className="sr-only">
                            {t('Minimum price')}
                        </label>
                        <Input
                            id="filter-min"
                            inputMode="decimal"
                            placeholder={t('Min')}
                            value={draft.min_price}
                            onChange={(e) => setDraft({ ...draft, min_price: e.target.value })}
                        />
                        <span aria-hidden="true" className="text-stone">
                            –
                        </span>
                        <label htmlFor="filter-max" className="sr-only">
                            {t('Maximum price')}
                        </label>
                        <Input
                            id="filter-max"
                            inputMode="decimal"
                            placeholder={t('Max')}
                            value={draft.max_price}
                            onChange={(e) => setDraft({ ...draft, max_price: e.target.value })}
                        />
                    </div>
                </FilterGroup>
            </div>

            <div className="flex gap-3 border-t border-line p-5">
                {onClear && (
                    <Button type="button" variant="secondary" className="flex-1" onClick={onClear}>
                        {t('Clear filters')}
                    </Button>
                )}
                <Button type="submit" className="flex-1">
                    {t('Apply')}
                </Button>
            </div>
        </form>
    );
}
