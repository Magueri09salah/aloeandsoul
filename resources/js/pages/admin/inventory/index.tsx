import { Link, router, useForm } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import type { Paginated } from '@/types';

import { EmptyState, PageHeader, Pagination, Panel, Table, td, th } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface Row {
    id: number;
    product_id: number;
    product_name: string;
    product_status: string;
    name: string | null;
    sku: string | null;
    stock: number;
    track_stock: boolean;
    threshold: number;
    is_low: boolean;
    is_active: boolean;
}

interface Props {
    variants: Paginated<Row>;
    filters: { q: string; level: 'low' | 'out' | null };
    canManage: boolean;
}

export default function Inventory({ variants, filters, canManage }: Props) {
    const [q, setQ] = useState(filters.q);
    const [adjusting, setAdjusting] = useState<Row | null>(null);

    const apply = (next: Partial<Props['filters']>) => {
        const merged = { ...filters, q, ...next };
        const query = Object.fromEntries(Object.entries(merged).filter(([, v]) => v !== null && v !== ''));
        router.get('/admin/inventory', query, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <>
            <PageHeader title="Stock" description="Stock is reserved when an order is placed and returned automatically if the order is cancelled. Every change is recorded." />
            <div className="mb-4 flex flex-wrap gap-2" role="group" aria-label="Stock level">
                {[
                    { value: null, label: 'All' },
                    { value: 'low' as const, label: 'Low stock' },
                    { value: 'out' as const, label: 'Out of stock' },
                ].map((l) => (
                    <button
                        key={l.label}
                        type="button"
                        aria-pressed={filters.level === l.value}
                        onClick={() => apply({ level: l.value })}
                        className={cn('h-9 rounded-full border px-3.5 text-sm', filters.level === l.value ? 'border-ink bg-ink text-ivory' : 'border-line bg-white hover:border-stone')}
                    >
                        {l.label}
                    </button>
                ))}
            </div>
            <Panel padded={false}>
                <form
                    onSubmit={(e: FormEvent) => {
                        e.preventDefault();
                        apply({});
                    }}
                    className="border-b border-line p-4"
                >
                    <div className="relative max-w-md">
                        <label htmlFor="q" className="sr-only">
                            Search stock
                        </label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone" aria-hidden="true" />
                        <Input id="q" type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Product, option or SKU" className="h-10 pl-9" />
                    </div>
                </form>
                {variants.data.length === 0 ? (
                    <EmptyState title="Nothing to show." />
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>Product</th>
                                <th className={th}>SKU</th>
                                <th className={cn(th, 'text-right')}>In stock</th>
                                <th className={th}>
                                    <span className="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {variants.data.map((v) => (
                                <tr key={v.id}>
                                    <td className={td}>
                                        <Link href={`/admin/products/${v.product_id}/edit`} className="hover:underline">
                                            {v.product_name}
                                        </Link>
                                        {v.name && <span className="text-stone"> — {v.name}</span>}
                                        <span className="ml-2 inline-flex gap-1">
                                            {v.product_status === 'draft' && <Badge tone="warning">Draft</Badge>}
                                            {!v.is_active && <Badge tone="neutral">Not for sale</Badge>}
                                        </span>
                                    </td>
                                    <td className={cn(td, 'text-stone')}>{v.sku ?? '—'}</td>
                                    <td className={cn(td, 'text-right tabular-nums')}>
                                        {v.track_stock ? (
                                            <span className={cn(v.stock === 0 ? 'text-clay' : v.is_low && 'text-dune-deep')}>{v.stock}</span>
                                        ) : (
                                            <span className="text-stone">Not tracked</span>
                                        )}
                                    </td>
                                    <td className={cn(td, 'text-right whitespace-nowrap')}>
                                        <Button asChild size="sm" variant="ghost">
                                            <Link href={`/admin/inventory/${v.id}/history`}>History</Link>
                                        </Button>
                                        {canManage && v.track_stock && (
                                            <Button size="sm" variant="secondary" onClick={() => setAdjusting(v)}>
                                                Adjust
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                )}
                <Pagination page={variants} />
            </Panel>
            <Dialog open={adjusting !== null} onOpenChange={(o) => !o && setAdjusting(null)}>
                {adjusting && (
                    <DialogContent title="Adjust stock" description={`${adjusting.product_name}${adjusting.name ? ` — ${adjusting.name}` : ''}`}>
                        <AdjustForm row={adjusting} onDone={() => setAdjusting(null)} />
                    </DialogContent>
                )}
            </Dialog>
        </>
    );
}

function AdjustForm({ row, onDone }: { row: Row; onDone: () => void }) {
    const form = useForm({ stock: row.stock, note: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(`/admin/inventory/${row.id}`, { preserveScroll: true, onSuccess: onDone });
    };

    return (
        <form onSubmit={submit} noValidate className="space-y-4">
            <Field label="Units in stock now" name="stock" error={form.errors.stock} hint={`Currently ${row.stock}. Enter the counted quantity.`} required>
                {(p) => <Input {...p} type="number" min={0} value={form.data.stock} onChange={(e) => form.setData('stock', Math.max(0, Number.parseInt(e.target.value || '0', 10)))} />}
            </Field>
            <Field label="Reason" name="note" error={form.errors.note} hint="For example: delivery received, stock count, damaged." optional>
                {(p) => <Input {...p} value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} maxLength={255} />}
            </Field>
            <div className="flex justify-end">
                <Button type="submit" loading={form.processing}>
                    Save stock
                </Button>
            </div>
        </form>
    );
}
