import { router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { EmptyState, PageHeader, Panel } from '@/components/admin/ui';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { ConfirmDialog, Dialog, DialogContent } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input, Label, Textarea } from '@/components/ui/input';

interface Faq {
    id: number;
    question: string;
    answer: string;
    sort_order: number;
    is_published: boolean;
    show_on_home: boolean;
}

export default function Faqs({ faqs }: { faqs: Faq[] }) {
    const [editing, setEditing] = useState<Faq | 'new' | null>(null);

    return (
        <>
            <PageHeader
                title="FAQ"
                description="Answers shown on the FAQ page. Up to four can also appear on the home page."
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus aria-hidden="true" /> Add question
                    </Button>
                }
            />
            <Panel padded={false}>
                {faqs.length === 0 ? (
                    <EmptyState title="No questions yet." />
                ) : (
                    <ul className="divide-y divide-line">
                        {faqs.map((f) => (
                            <li key={f.id} className="flex items-start justify-between gap-4 px-5 py-4">
                                <div className="min-w-0">
                                    <p className="font-medium">{f.question}</p>
                                    <p className="mt-1 line-clamp-2 text-sm text-stone">{f.answer}</p>
                                    <div className="mt-2 flex gap-2">
                                        {!f.is_published && <Badge tone="warning">Hidden</Badge>}
                                        {f.show_on_home && <Badge tone="info">On home page</Badge>}
                                    </div>
                                </div>
                                <Button size="sm" variant="ghost" onClick={() => setEditing(f)}>
                                    Edit
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </Panel>
            <Dialog open={editing !== null} onOpenChange={(o) => !o && setEditing(null)}>
                {editing !== null && (
                    <DialogContent title={editing === 'new' ? 'New question' : 'Edit question'} className="max-w-xl">
                        <FaqForm faq={editing === 'new' ? null : editing} onDone={() => setEditing(null)} />
                    </DialogContent>
                )}
            </Dialog>
        </>
    );
}

function FaqForm({ faq, onDone }: { faq: Faq | null; onDone: () => void }) {
    const form = useForm({
        question: faq?.question ?? '',
        answer: faq?.answer ?? '',
        sort_order: faq?.sort_order ?? 0,
        is_published: faq?.is_published ?? true,
        show_on_home: faq?.show_on_home ?? false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (faq) form.put(`/admin/faqs/${faq.id}`, opts);
        else form.post('/admin/faqs', opts);
    };

    return (
        <form onSubmit={submit} noValidate className="space-y-4">
            <Field label="Question" name="question" error={form.errors.question} required>
                {(p) => <Input {...p} value={form.data.question} onChange={(e) => form.setData('question', e.target.value)} maxLength={255} />}
            </Field>
            <Field label="Answer" name="answer" error={form.errors.answer} required>
                {(p) => <Textarea {...p} rows={5} value={form.data.answer} onChange={(e) => form.setData('answer', e.target.value)} maxLength={3000} />}
            </Field>
            <Field label="Position" name="sort_order" error={form.errors.sort_order}>
                {(p) => <Input {...p} type="number" className="max-w-28" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', Number(e.target.value))} />}
            </Field>
            <div className="flex flex-wrap gap-6">
                <div className="flex items-center gap-2.5">
                    <Checkbox id="faq-published" checked={form.data.is_published} onCheckedChange={(v) => form.setData('is_published', v === true)} />
                    <Label htmlFor="faq-published" className="font-normal">
                        Published
                    </Label>
                </div>
                <div className="flex items-center gap-2.5">
                    <Checkbox id="faq-home" checked={form.data.show_on_home} onCheckedChange={(v) => form.setData('show_on_home', v === true)} />
                    <Label htmlFor="faq-home" className="font-normal">
                        Show on home page
                    </Label>
                </div>
            </div>
            <div className="flex justify-between gap-2 pt-2">
                {faq ? (
                    <ConfirmDialog
                        trigger={
                            <Button type="button" variant="destructive-outline">
                                Delete
                            </Button>
                        }
                        title="Delete this question?"
                        description={faq.question}
                        confirmLabel="Delete"
                        onConfirm={() => router.delete(`/admin/faqs/${faq.id}`, { onSuccess: onDone })}
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
