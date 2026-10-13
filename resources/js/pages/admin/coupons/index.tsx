import { router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { EmptyState, PageHeader, Panel, Table, td, th } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox, Switch } from '@/components/ui/checkbox';
import { ConfirmDialog, Dialog, DialogContent } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input, Label, Select } from '@/components/ui/input';

interface Coupon {
    id: number;
    code: string;
    description: string | null;
    type: 'percent' | 'fixed';
    value: string;
    min_subtotal: string;
    max_uses: number | null;
    uses_count: number;
    once_per_customer: boolean;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
    expired: boolean;
}

export default function Coupons({ coupons, currency }: { coupons: Coupon[]; currency: string }) {
    const [editing, setEditing] = useState<Coupon | 'new' | null>(null);

    return (
        <>
            <PageHeader
                title="Discount codes"
                description="Customers enter a code in the cart or at checkout. Discounts are always recalculated on the server."
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus aria-hidden="true" /> New code
                    </Button>
                }
            />
            <Panel padded={false}>
                {coupons.length === 0 ? (
                    <EmptyState title="No discount codes yet." />
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>Code</th>
                                <th className={th}>Discount</th>
                                <th className={th}>Used</th>
                                <th className={th}>Valid</th>
                                <th className={th}>
                                    <span className="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {coupons.map((c) => (
                                <tr key={c.id}>
                                    <td className={td}>
                                        <span className="font-mono font-medium">{c.code}</span>
                                        {c.description && <span className="block text-xs text-stone">{c.description}</span>}
                                    </td>
                                    <td className={td}>
                                        {c.type === 'percent' ? `${c.value}%` : `${c.value} ${currency}`}
                                        {c.min_subtotal && <span className="block text-xs text-stone">min. {c.min_subtotal} {currency}</span>}
                                    </td>
                                    <td className={`${td} tabular-nums`}>
                                        {c.uses_count}
                                        {c.max_uses !== null && ` / ${c.max_uses}`}
                                    </td>
                                    <td className={td}>
                                        {!c.is_active ? <Badge tone="neutral">Off</Badge> : c.expired ? <Badge tone="warning">Expired</Badge> : <Badge tone="success">Active</Badge>}
                                    </td>
                                    <td className={`${td} text-right`}>
                                        <Button size="sm" variant="ghost" onClick={() => setEditing(c)}>
                                            Edit
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                )}
            </Panel>
            <Dialog open={editing !== null} onOpenChange={(o) => !o && setEditing(null)}>
                {editing !== null && (
                    <DialogContent title={editing === 'new' ? 'New discount code' : `Edit ${editing.code}`} className="max-w-xl">
                        <CouponForm coupon={editing === 'new' ? null : editing} currency={currency} onDone={() => setEditing(null)} />
                    </DialogContent>
                )}
            </Dialog>
        </>
    );
}

function CouponForm({ coupon, currency, onDone }: { coupon: Coupon | null; currency: string; onDone: () => void }) {
    const form = useForm({
        code: coupon?.code ?? '',
        description: coupon?.description ?? '',
        type: coupon?.type ?? 'percent',
        value: coupon?.value ?? '',
        min_subtotal: coupon?.min_subtotal ?? '',
        max_uses: coupon?.max_uses ?? '',
        once_per_customer: coupon?.once_per_customer ?? false,
        starts_at: coupon?.starts_at ?? '',
        ends_at: coupon?.ends_at ?? '',
        is_active: coupon?.is_active ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({ ...d, max_uses: d.max_uses === '' ? null : d.max_uses }));
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (coupon) form.put(`/admin/coupons/${coupon.id}`, opts);
        else form.post('/admin/coupons', opts);
    };

    return (
        <form onSubmit={submit} noValidate className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Code" name="code" error={form.errors.code} required>
                    {(p) => <Input {...p} value={form.data.code} onChange={(e) => form.setData('code', e.target.value.toUpperCase())} className="font-mono" maxLength={40} />}
                </Field>
                <Field label="Internal description" name="description" error={form.errors.description} optional>
                    {(p) => <Input {...p} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} maxLength={160} />}
                </Field>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Type" name="type" error={form.errors.type}>
                    {(p) => (
                        <Select {...p} value={form.data.type} onChange={(e) => form.setData('type', e.target.value as 'percent' | 'fixed')}>
                            <option value="percent">Percentage off</option>
                            <option value="fixed">Fixed amount off</option>
                        </Select>
                    )}
                </Field>
                <Field label={form.data.type === 'percent' ? 'Percentage' : `Amount (${currency})`} name="value" error={form.errors.value} required>
                    {(p) => <Input {...p} inputMode="decimal" value={form.data.value} onChange={(e) => form.setData('value', e.target.value)} />}
                </Field>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label={`Minimum order (${currency})`} name="min_subtotal" error={form.errors.min_subtotal} optional>
                    {(p) => <Input {...p} inputMode="decimal" value={form.data.min_subtotal} onChange={(e) => form.setData('min_subtotal', e.target.value)} />}
                </Field>
                <Field label="Total uses allowed" name="max_uses" error={form.errors.max_uses} optional hint="Leave empty for no limit.">
                    {(p) => <Input {...p} type="number" min={1} value={form.data.max_uses} onChange={(e) => form.setData('max_uses', e.target.value)} />}
                </Field>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Starts" name="starts_at" error={form.errors.starts_at} optional>
                    {(p) => <Input {...p} type="datetime-local" value={form.data.starts_at} onChange={(e) => form.setData('starts_at', e.target.value)} />}
                </Field>
                <Field label="Ends" name="ends_at" error={form.errors.ends_at} optional>
                    {(p) => <Input {...p} type="datetime-local" value={form.data.ends_at} onChange={(e) => form.setData('ends_at', e.target.value)} />}
                </Field>
            </div>
            <div className="flex items-center gap-3">
                <Checkbox id="once" checked={form.data.once_per_customer} onCheckedChange={(v) => form.setData('once_per_customer', v === true)} />
                <Label htmlFor="once" className="font-normal">
                    Once per customer (by phone number)
                </Label>
            </div>
            <div className="flex items-center gap-3">
                <Switch id="coupon-active" checked={form.data.is_active} onCheckedChange={(v) => form.setData('is_active', v)} />
                <Label htmlFor="coupon-active" className="font-normal">
                    Active
                </Label>
            </div>
            <div className="flex justify-between gap-2 pt-2">
                {coupon && coupon.uses_count === 0 ? (
                    <ConfirmDialog
                        trigger={
                            <Button type="button" variant="destructive-outline">
                                Delete
                            </Button>
                        }
                        title="Delete this code?"
                        description="It has never been used."
                        confirmLabel="Delete code"
                        onConfirm={() => router.delete(`/admin/coupons/${coupon.id}`, { onSuccess: onDone })}
                    />
                ) : (
                    <span />
                )}
                <Button type="submit" loading={form.processing}>
                    Save code
                </Button>
            </div>
        </form>
    );
}
