import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';

import type { ImageData } from '@/types';

import { ContentImage } from '@/components/admin/content-image';
import { PageHeader, Panel } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input, Textarea } from '@/components/ui/input';

interface Props {
    story: {
        heading: string;
        intro: string;
        image: ImageData;
        image_alt: string;
        has_custom_image: boolean;
        milestones: { place: string; text: string }[];
        chapters: { key: string; title: string; body: string }[];
        closing: string;
        disclaimer: string;
    };
}

export default function ContentStory({ story }: Props) {
    const form = useForm({
        heading: story.heading,
        intro: story.intro,
        image_alt: story.image_alt,
        milestones: story.milestones,
        chapters: story.chapters,
        closing: story.closing,
        disclaimer: story.disclaimer,
    });

    const err = (k: string) => (form.errors as Record<string, string | undefined>)[k];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put('/admin/content/story', { preserveScroll: true });
    };

    return (
        <>
            <PageHeader
                title="Our story"
                description="The founder’s story, in her own words. Paragraphs are separated by an empty line."
                actions={
                    <Button asChild variant="secondary" size="sm">
                        <a href="/our-story" target="_blank" rel="noopener">
                            View page
                        </a>
                    </Button>
                }
            />
            <div className="space-y-6">
                <Panel title="Photograph">
                    <ContentImage slot="story" image={story.image} isCustom={story.has_custom_image} />
                </Panel>
                <form onSubmit={submit} noValidate className="space-y-6">
                    <Panel title="Opening">
                        <div className="space-y-4">
                            <Field label="Heading" name="heading" error={err('heading')} required>
                                {(p) => <Input {...p} value={form.data.heading} onChange={(e) => form.setData('heading', e.target.value)} maxLength={120} />}
                            </Field>
                            <Field label="Opening line" name="intro" error={err('intro')} optional>
                                {(p) => <Textarea {...p} rows={2} value={form.data.intro} onChange={(e) => form.setData('intro', e.target.value)} maxLength={400} />}
                            </Field>
                            <Field label="Photograph description" name="image_alt" error={err('image_alt')} required>
                                {(p) => <Input {...p} value={form.data.image_alt} onChange={(e) => form.setData('image_alt', e.target.value)} maxLength={160} />}
                            </Field>
                        </div>
                    </Panel>

                    <Panel title="Milestones" description="Short facts shown as a timeline, in order.">
                        <div className="space-y-3">
                            {form.data.milestones.map((m, i) => (
                                <div key={i} className="flex flex-wrap gap-2">
                                    <Input
                                        aria-label={`Milestone ${i + 1} place`}
                                        aria-invalid={err(`milestones.${i}.place`) ? true : undefined}
                                        value={m.place}
                                        onChange={(e) => form.setData('milestones', form.data.milestones.map((x, j) => (j === i ? { ...x, place: e.target.value } : x)))}
                                        className="max-w-40"
                                        maxLength={40}
                                    />
                                    <Input
                                        aria-label={`Milestone ${i + 1} text`}
                                        aria-invalid={err(`milestones.${i}.text`) ? true : undefined}
                                        value={m.text}
                                        onChange={(e) => form.setData('milestones', form.data.milestones.map((x, j) => (j === i ? { ...x, text: e.target.value } : x)))}
                                        className="flex-1"
                                        maxLength={200}
                                    />
                                    <Button type="button" variant="ghost" size="icon" aria-label={`Remove milestone ${i + 1}`} onClick={() => form.setData('milestones', form.data.milestones.filter((_, j) => j !== i))}>
                                        <Trash2 aria-hidden="true" />
                                    </Button>
                                </div>
                            ))}
                            {form.data.milestones.length < 6 && (
                                <Button type="button" variant="ghost" size="sm" onClick={() => form.setData('milestones', [...form.data.milestones, { place: '', text: '' }])}>
                                    <Plus aria-hidden="true" /> Add milestone
                                </Button>
                            )}
                        </div>
                    </Panel>

                    {form.data.chapters.map((c, i) => (
                        <Panel
                            key={i}
                            title={c.title || `Chapter ${i + 1}`}
                            actions={
                                form.data.chapters.length > 1 && (
                                    <Button type="button" variant="ghost" size="sm" className="text-clay" onClick={() => form.setData('chapters', form.data.chapters.filter((_, j) => j !== i))}>
                                        <Trash2 aria-hidden="true" /> Remove
                                    </Button>
                                )
                            }
                        >
                            <div className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-3">
                                    <Field label="Title" name={`chapters.${i}.title`} error={err(`chapters.${i}.title`)} required className="sm:col-span-2">
                                        {(p) => <Input {...p} value={c.title} onChange={(e) => form.setData('chapters', form.data.chapters.map((x, j) => (j === i ? { ...x, title: e.target.value } : x)))} maxLength={80} />}
                                    </Field>
                                    <Field label="Link name" name={`chapters.${i}.key`} error={err(`chapters.${i}.key`)} hint={`/our-story#${c.key || '…'}`} required>
                                        {(p) => <Input {...p} value={c.key} onChange={(e) => form.setData('chapters', form.data.chapters.map((x, j) => (j === i ? { ...x, key: e.target.value } : x)))} maxLength={30} />}
                                    </Field>
                                </div>
                                <Field label="Text" name={`chapters.${i}.body`} error={err(`chapters.${i}.body`)} required>
                                    {(p) => (
                                        <Textarea
                                            {...p}
                                            rows={12}
                                            className="font-serif"
                                            value={c.body}
                                            onChange={(e) => form.setData('chapters', form.data.chapters.map((x, j) => (j === i ? { ...x, body: e.target.value } : x)))}
                                        />
                                    )}
                                </Field>
                            </div>
                        </Panel>
                    ))}
                    {form.data.chapters.length < 6 && (
                        <Button type="button" variant="secondary" onClick={() => form.setData('chapters', [...form.data.chapters, { key: '', title: '', body: '' }])}>
                            <Plus aria-hidden="true" /> Add chapter
                        </Button>
                    )}

                    <Panel title="Closing">
                        <div className="space-y-4">
                            <Field label="Closing text" name="closing" error={err('closing')} optional>
                                {(p) => <Textarea {...p} rows={3} value={form.data.closing} onChange={(e) => form.setData('closing', e.target.value)} maxLength={600} />}
                            </Field>
                            <Field label="Note at the end" name="disclaimer" error={err('disclaimer')} hint="Keep a sentence saying the story is personal and not medical advice." optional>
                                {(p) => <Textarea {...p} rows={2} value={form.data.disclaimer} onChange={(e) => form.setData('disclaimer', e.target.value)} maxLength={600} />}
                            </Field>
                        </div>
                    </Panel>

                    <div className="sticky bottom-0 z-10 flex justify-end border-t border-line bg-panel/95 py-3 backdrop-blur">
                        <Button type="submit" loading={form.processing}>
                            Save story
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
