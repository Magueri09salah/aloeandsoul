import { router, useForm } from '@inertiajs/react';
import { ExternalLink, Plus, Upload } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import type { Collaborator } from '@/types';

import { EmptyState, PageHeader, Panel } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox, Switch } from '@/components/ui/checkbox';
import { ConfirmDialog, Dialog, DialogContent } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input, Label, Select, Textarea } from '@/components/ui/input';
import { useObjectUrl } from '@/hooks/use-object-url';
import { cn } from '@/lib/utils';

type Row = Collaborator & { sort_order: number; is_visible: boolean };

interface Props {
    collaborators: Row[];
    section: { enabled: boolean; heading: string; intro: string };
}

export default function Collaborators({ collaborators, section }: Props) {
    const [editing, setEditing] = useState<Row | 'new' | null>(null);

    return (
        <>
            <PageHeader
                title="Collaborators"
                description="People, brands and places Aloe & Soul works with. Visible collaborators appear on the home page, in this order."
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus aria-hidden="true" /> Add collaborator
                    </Button>
                }
            />

            <SectionSettings section={section} />

            <Panel padded={false} className="mt-6">
                {collaborators.length === 0 ? (
                    <EmptyState title="No collaborators yet.">Add the first one to show the section on the home page.</EmptyState>
                ) : (
                    <ul className="divide-y divide-line">
                        {collaborators.map((c) => (
                            <li key={c.id} className="flex items-center gap-4 px-5 py-4">
                                <div className={cn('size-14 shrink-0 overflow-hidden rounded-full', c.image_style === 'logo' ? 'border border-line bg-white p-1.5' : 'bg-sand')}>
                                    {c.image && <img src={c.image.src} alt="" className={cn('h-full w-full', c.image_style === 'logo' ? 'object-contain' : 'object-cover')} />}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">
                                        {c.name} {!c.is_visible && <Badge tone="neutral">Hidden</Badge>}
                                    </p>
                                    {c.description && <p className="truncate text-sm text-stone">{c.description}</p>}
                                    {c.url && (
                                        <a href={c.url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 text-xs text-stone underline underline-offset-4">
                                            {c.url.replace(/^https:\/\//, '')} <ExternalLink className="size-3" aria-hidden="true" />
                                        </a>
                                    )}
                                </div>
                                <span className="hidden text-sm text-stone sm:block">Position {c.sort_order}</span>
                                <Button size="sm" variant="ghost" onClick={() => setEditing(c)}>
                                    Edit
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </Panel>

            <Dialog open={editing !== null} onOpenChange={(o) => !o && setEditing(null)}>
                {editing !== null && (
                    <DialogContent title={editing === 'new' ? 'New collaborator' : `Edit ${editing.name}`} className="max-w-xl">
                        <CollaboratorForm collaborator={editing === 'new' ? null : editing} nextPosition={collaborators.length} onDone={() => setEditing(null)} />
                    </DialogContent>
                )}
            </Dialog>
        </>
    );
}

function SectionSettings({ section }: { section: Props['section'] }) {
    const form = useForm({ enabled: section.enabled, heading: section.heading, intro: section.intro });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put('/admin/collaborators/section', { preserveScroll: true });
    };

    return (
        <Panel title="Home page section" actions={<Switch aria-label="Show the section on the home page" checked={form.data.enabled} onCheckedChange={(v) => form.setData('enabled', v)} />}>
            <form onSubmit={submit} noValidate className="grid gap-4 sm:grid-cols-3">
                <Field label="Heading" name="heading" error={form.errors.heading} required>
                    {(p) => <Input {...p} value={form.data.heading} onChange={(e) => form.setData('heading', e.target.value)} maxLength={80} />}
                </Field>
                <Field label="Introduction" name="intro" error={form.errors.intro} optional className="sm:col-span-2">
                    {(p) => <Input {...p} value={form.data.intro} onChange={(e) => form.setData('intro', e.target.value)} maxLength={300} />}
                </Field>
                <div className="sm:col-span-3">
                    <Button type="submit" variant="secondary" loading={form.processing} disabled={!form.isDirty}>
                        Save section
                    </Button>
                </div>
            </form>
        </Panel>
    );
}

function CollaboratorForm({ collaborator, nextPosition, onDone }: { collaborator: Row | null; nextPosition: number; onDone: () => void }) {
    const form = useForm<{
        name: string;
        description: string;
        url: string;
        image_style: 'photo' | 'logo';
        sort_order: number;
        is_visible: boolean;
        image: File | null;
        remove_image: boolean;
    }>({
        name: collaborator?.name ?? '',
        description: collaborator?.description ?? '',
        url: collaborator?.url ?? '',
        image_style: collaborator?.image_style ?? 'photo',
        sort_order: collaborator?.sort_order ?? nextPosition,
        is_visible: collaborator?.is_visible ?? true,
        image: null,
        remove_image: false,
    });

    const preview = useObjectUrl(form.data.image);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { forceFormData: true, preserveScroll: true, onSuccess: onDone };
        if (collaborator) {
            form.transform((d) => ({ ...d, _method: 'put' }));
            form.post(`/admin/collaborators/${collaborator.id}`, options);
        } else {
            form.post('/admin/collaborators', options);
        }
    };

    const imageSrc = preview ?? (form.data.remove_image ? null : (collaborator?.image?.src ?? null));
    const logo = form.data.image_style === 'logo';

    return (
        <form onSubmit={submit} noValidate className="space-y-4">
            <div className="flex items-center gap-4">
                <div className={cn('size-20 shrink-0 overflow-hidden rounded-full', logo ? 'border border-line bg-white p-2' : 'bg-sand')}>
                    {imageSrc && <img src={imageSrc} alt="" className={cn('h-full w-full', logo ? 'object-contain' : 'object-cover')} />}
                </div>
                <div className="flex-1 space-y-2">
                    <label className="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-stone/40 px-3 py-2.5 text-sm hover:border-ink">
                        <Upload className="size-4 text-stone" aria-hidden="true" />
                        {form.data.image ? form.data.image.name : 'Choose a photo or logo'}
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            className="sr-only"
                            onChange={(e) => {
                                form.setData('image', e.target.files?.[0] ?? null);
                                form.setData('remove_image', false);
                            }}
                        />
                    </label>
                    {form.errors.image && (
                        <p className="text-sm text-clay" role="alert">
                            {form.errors.image}
                        </p>
                    )}
                    {collaborator?.image && !form.data.image && (
                        <div className="flex items-center gap-2">
                            <Checkbox id="remove-image" checked={form.data.remove_image} onCheckedChange={(v) => form.setData('remove_image', v === true)} />
                            <Label htmlFor="remove-image" className="font-normal">
                                Remove the image
                            </Label>
                        </div>
                    )}
                </div>
            </div>
            <Field label="Image type" name="image_style" error={form.errors.image_style} hint="Photos fill the circle; logos are shown whole on white.">
                {(p) => (
                    <Select {...p} value={form.data.image_style} onChange={(e) => form.setData('image_style', e.target.value as 'photo' | 'logo')}>
                        <option value="photo">Photo (person or place)</option>
                        <option value="logo">Logo (brand)</option>
                    </Select>
                )}
            </Field>
            <Field label="Name" name="name" error={form.errors.name} required>
                {(p) => <Input {...p} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} maxLength={120} />}
            </Field>
            <Field label="Short description" name="description" error={form.errors.description} hint="e.g. Yoga teacher, Essaouira" optional>
                {(p) => <Textarea {...p} rows={2} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} maxLength={255} />}
            </Field>
            <Field label="Website or Instagram link" name="url" error={form.errors.url} hint="Full address starting with https://" optional>
                {(p) => <Input {...p} type="url" inputMode="url" value={form.data.url} onChange={(e) => form.setData('url', e.target.value)} placeholder="https://" maxLength={255} />}
            </Field>
            <div className="grid grid-cols-2 items-end gap-4">
                <Field label="Position" name="sort_order" error={form.errors.sort_order} hint="Lower numbers first.">
                    {(p) => <Input {...p} type="number" min={0} value={form.data.sort_order} onChange={(e) => form.setData('sort_order', Number(e.target.value))} />}
                </Field>
                <div className="flex items-center gap-3 pb-8">
                    <Switch id="visible" checked={form.data.is_visible} onCheckedChange={(v) => form.setData('is_visible', v)} />
                    <Label htmlFor="visible" className="font-normal">
                        Show on the home page
                    </Label>
                </div>
            </div>
            <div className="flex justify-between gap-2 pt-2">
                {collaborator ? (
                    <ConfirmDialog
                        trigger={
                            <Button type="button" variant="destructive-outline">
                                Remove
                            </Button>
                        }
                        title={`Remove ${collaborator.name}?`}
                        description="They will no longer appear on the home page. Their image is deleted."
                        confirmLabel="Remove"
                        onConfirm={() => router.delete(`/admin/collaborators/${collaborator.id}`, { preserveScroll: true, onSuccess: onDone })}
                    />
                ) : (
                    <span />
                )}
                <Button type="submit" loading={form.processing}>
                    {collaborator ? 'Save' : 'Add collaborator'}
                </Button>
            </div>
        </form>
    );
}
