import { router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { ContentLanguageSwitch, hasFrenchErrors, useContentLanguage } from '@/components/admin/content-language';
import { EmptyState, PageHeader, Panel, Table, td, th } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/checkbox';
import { ConfirmDialog, Dialog, DialogContent } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input, Label } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface Zone {
    id: number;
    name: string;
    fee: string;
    free_over: string;
    estimate: string | null;
    name_fr: string | null;
    estimate_fr: string | null;
    is_active: boolean;
    sort_order: number;
}

export default function Zones({ zones, currency }: { zones: Zone[]; currency: string }) {
    const [editing, setEditing] = useState<Zone | 'new' | null>(null);

    return (
        <>
            <PageHeader
                title="Delivery areas"
                description="Customers choose an area at checkout; its fee is added to the amount paid on delivery. Checkout stays closed until at least one area is active."
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus aria-hidden="true" /> Add area
                    </Button>
                }
            />
            <Panel padded={false}>
                {zones.length === 0 ? (
                    <EmptyState title="No delivery areas yet.">Add the cities or regions you deliver to, with their fees.</EmptyState>
                ) : (
                    <Table>
                        <thead>
                            <tr>
                                <th className={th}>Area</th>
                                <th className={cn(th, 'text-right')}>Fee</th>
                                <th className={cn(th, 'text-right')}>Free from</th>
                                <th className={th}>Typical time</th>
                                <th className={th}>
                                    <span className="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {zones.map((z) => (
                                <tr key={z.id}>
                                    <td className={td}>
                                        {z.name} {!z.is_active && <Badge tone="warning">Off</Badge>}
                                    </td>
                                    <td className={cn(td, 'text-right tabular-nums')}>
                                        {z.fee} {currency}
                                    </td>
                                    <td className={cn(td, 'text-right tabular-nums')}>{z.free_over ? `${z.free_over} ${currency}` : '—'}</td>
                                    <td className={td}>{z.estimate ?? '—'}</td>
                                    <td className={cn(td, 'text-right')}>
                                        <Button size="sm" variant="ghost" onClick={() => setEditing(z)}>
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
                    <DialogContent title={editing === 'new' ? 'New delivery area' : `Edit ${editing.name}`}>
                        <ZoneForm zone={editing === 'new' ? null : editing} currency={currency} onDone={() => setEditing(null)} />
                    </DialogContent>
                )}
            </Dialog>
        </>
    );
}

function ZoneForm({ zone, currency, onDone }: { zone: Zone | null; currency: string; onDone: () => void }) {
    const form = useForm({
        name: zone?.name ?? '',
        fee: zone?.fee ?? '',
        free_over: zone?.free_over ?? '',
        estimate: zone?.estimate ?? '',
        name_fr: zone?.name_fr ?? '',
        estimate_fr: zone?.estimate_fr ?? '',
        is_active: zone?.is_active ?? true,
        sort_order: zone?.sort_order ?? 0,
    });

    const c = useContentLanguage();
    const err = (key: string) => (form.errors as Record<string, string | undefined>)[key];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (zone) form.put(`/admin/delivery-zones/${zone.id}`, opts);
        else form.post('/admin/delivery-zones', opts);
    };

    return (
        <form onSubmit={submit} noValidate className="space-y-4">
            <ContentLanguageSwitch value={c.lang} onChange={c.setLang} frenchErrors={hasFrenchErrors(form.errors)} />
            <Field
                label={c.label('Area name')}
                name={c.field('name')}
                error={err(c.field('name'))}
                hint={c.fr ? 'For example Autres villes. City names can stay the same.' : 'For example Marrakech, or Other cities.'}
                required={!c.fr}
                optional={c.fr}
            >
                {(p) => <Input {...p} value={form.data[c.field('name')]} onChange={(e) => form.setData(c.field('name'), e.target.value)} placeholder={c.source(form.data.name)} maxLength={80} />}
            </Field>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label={`Delivery fee (${currency})`} name="fee" error={form.errors.fee} required>
                    {(p) => <Input {...p} inputMode="decimal" value={form.data.fee} onChange={(e) => form.setData('fee', e.target.value)} />}
                </Field>
                <Field label={`Free delivery from (${currency})`} name="free_over" error={form.errors.free_over} optional>
                    {(p) => <Input {...p} inputMode="decimal" value={form.data.free_over} onChange={(e) => form.setData('free_over', e.target.value)} />}
                </Field>
            </div>
            <Field label={c.label('Typical delivery time')} name={c.field('estimate')} error={err(c.field('estimate'))} hint={c.fr ? 'e.g. 1 à 2 jours.' : 'Shown to customers, e.g. 1–2 days.'} optional>
                {(p) => (
                    <Input {...p} value={form.data[c.field('estimate')]} onChange={(e) => form.setData(c.field('estimate'), e.target.value)} placeholder={c.source(form.data.estimate)} maxLength={80} />
                )}
            </Field>
            <div className="flex items-center gap-3">
                <Switch id="zone-active" checked={form.data.is_active} onCheckedChange={(v) => form.setData('is_active', v)} />
                <Label htmlFor="zone-active" className="font-normal">
                    Offered at checkout
                </Label>
            </div>
            <div className="flex justify-between gap-2 pt-2">
                {zone ? (
                    <ConfirmDialog
                        trigger={
                            <Button type="button" variant="destructive-outline">
                                Delete
                            </Button>
                        }
                        title="Delete this delivery area?"
                        description="If past orders used it, it will be turned off instead."
                        confirmLabel="Delete"
                        onConfirm={() => router.delete(`/admin/delivery-zones/${zone.id}`, { onSuccess: onDone })}
                    />
                ) : (
                    <span />
                )}
                <Button type="submit" loading={form.processing}>
                    Save
                </Button>
            </div>
        </form>
    );
}
