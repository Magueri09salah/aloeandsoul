import { Link, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import type { ImageData, Option, Paginated } from '@/types';

import { EmptyState, PageHeader, Pagination, Panel, ProductStatusBadge, Table, td, th } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { Input, Select } from '@/components/ui/input';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';

interface Row {
    id: number;
    name: string;
    category: string;
    status: string;
    status_label: string;
    image: ImageData | null;
    min_price_minor: number | null;
    max_price_minor: number | null;
    total_stock: number;
    variant_count: number;
    is_featured: boolean;
    is_bestseller: boolean;
    blockers: number;
}

interface Props {
    products: Paginated<Row>;
    filters: { q: string; category: number | null; status: string | null; sort: string };
    categories: { id: number; name: string }[];
    statuses: Option[];
    currency: string;
    canManage: boolean;
}

export default function ProductsIndex({ products, filters, categories, statuses, currency, canManage }: Props) {
    const [q, setQ] = useState(filters.q);

    const apply = (next: Partial<Props['filters']>) => {
        const merged = { ...filters, q, ...next };
        const query = Object.fromEntries(Object.entries(merged).filter(([, v]) => v !== null && v !== '' && v !== undefined));
        router.get('/admin/products', query, { preserveState: true, preserveScroll: true, replace: true });
    };

    const price = (r: Row) => {
        if (r.min_price_minor === null) return <span className="text-stone">No price</span>;
        if (r.min_price_minor === r.max_price_minor) return formatMoney(r.min_price_minor, currency);
        return `${formatMoney(r.min_price_minor, currency)} – ${formatMoney(r.max_price_minor ?? r.min_price_minor, currency)}`;
    };

    return (
        <>
            <PageHeader
                title="Products"
                description="Drafts are hidden from the shop. A product can be published once it has a price, an image and a description."
                actions={
                    canManage && (
                        <Button asChild>
                            <Link href="/admin/products/create">
                                <Plus aria-hidden="true" />
                                Add product
                            </Link>
                        </Button>
                    )
                }
            />
            <Panel padded={false}>
                <form
                    onSubmit={(e: FormEvent) => {
                        e.preventDefault();
                        apply({});
                    }}
                    className="grid gap-3 border-b border-line p-4 sm:grid-cols-[1fr_auto_auto_auto]"
                >
                    <div className="relative">
                        <label htmlFor="q" className="sr-only">
                            Search products
                        </label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone" aria-hidden="true" />
                        <Input id="q" type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Name or SKU" className="h-10 pl-9" />
                    </div>
                    <Select aria-label="Category" value={filters.category ?? ''} onChange={(e) => apply({ category: e.target.value ? Number(e.target.value) : null })} className="h-10">
                        <option value="">All categories</option>
                        {categories.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.name}
                            </option>
                        ))}
                    </Select>
                    <Select aria-label="Status" value={filters.status ?? ''} onChange={(e) => apply({ status: e.target.value || null })} className="h-10">
                        <option value="">All statuses</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </Select>
                    <Select aria-label="Sort" value={filters.sort} onChange={(e) => apply({ sort: e.target.value })} className="h-10">
                        <option value="updated">Recently edited</option>
                        <option value="name">Name</option>
                        <option value="stock">Lowest stock</option>
                    </Select>
                </form>

                {products.data.length === 0 ? (
                    <EmptyState title="No products found." />
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>Product</th>
                                <th className={th}>Status</th>
                                <th className={th}>Price</th>
                                <th className={cn(th, 'text-right')}>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.data.map((p) => (
                                <tr key={p.id} className="hover:bg-panel/50">
                                    <td className={td}>
                                        <div className="flex items-center gap-3">
                                            <div className="size-11 shrink-0 overflow-hidden rounded bg-sand">
                                                {p.image && <img src={p.image.src} alt="" className="h-full w-full object-cover" loading="lazy" />}
                                            </div>
                                            <div className="min-w-0">
                                                {canManage ? (
                                                    <Link href={`/admin/products/${p.id}/edit`} className="font-medium hover:underline">
                                                        {p.name}
                                                    </Link>
                                                ) : (
                                                    <span className="font-medium">{p.name}</span>
                                                )}
                                                <span className="block text-xs text-stone">
                                                    {p.category}
                                                    {p.variant_count > 1 && `, ${p.variant_count} options`}
                                                    {p.is_featured && ', featured'}
                                                    {p.is_bestseller && ', bestseller'}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td className={td}>
                                        <ProductStatusBadge status={p.status} label={p.status_label} />
                                        {p.blockers > 0 && <span className="mt-1 block text-xs text-stone">{p.blockers} thing(s) to complete</span>}
                                    </td>
                                    <td className={cn(td, 'whitespace-nowrap tabular-nums')}>{price(p)}</td>
                                    <td className={cn(td, 'text-right tabular-nums', p.total_stock === 0 && 'text-clay')}>{p.total_stock}</td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                )}
                <Pagination page={products} />
            </Panel>
        </>
    );
}
