import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';

import type { ImageData } from '@/types';

import { ContentImage } from '@/components/admin/content-image';
import { ContentLanguageSwitch, hasFrenchErrors, useContentLanguage } from '@/components/admin/content-language';
import { PageHeader, Panel } from '@/components/admin/ui';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input, Textarea } from '@/components/ui/input';

interface Milestone {
    place: string;
    text: string;
    place_fr: string;
    text_fr: string;
}

interface Chapter {
    key: string;
    title: string;
    body: string;
    title_fr: string;
    body_fr: string;
}

type Translated<T extends string> = Record<T | `${T}_fr`, string>;

interface Props {
    story: Translated<'heading' | 'intro' | 'image_alt' | 'closing' | 'disclaimer'> & {
        image: ImageData;
        has_custom_image: boolean;
        milestones: Partial<Milestone>[];
        chapters: Partial<Chapter>[];
    };
}

export default function ContentStory({ story }: Props) {
    const form = useForm({
        heading: story.heading,
        intro: story.intro,
        image_alt: story.image_alt,
        closing: story.closing,
        disclaimer: story.disclaimer,
        heading_fr: story.heading_fr ?? '',
        intro_fr: story.intro_fr ?? '',
        image_alt_fr: story.image_alt_fr ?? '',
        closing_fr: story.closing_fr ?? '',
        disclaimer_fr: story.disclaimer_fr ?? '',
        milestones: story.milestones.map((m) => ({ place: m.place ?? '', text: m.text ?? '', place_fr: m.place_fr ?? '', text_fr: m.text_fr ?? '' })) as Milestone[],
        chapters: story.chapters.map((c) => ({ key: c.key ?? '', title: c.title ?? '', body: c.body ?? '', title_fr: c.title_fr ?? '', body_fr: c.body_fr ?? '' })) as Chapter[],
    });
    const lang = useContentLanguage();
    const { fr } = lang;

    const err = (k: string) => (form.errors as Record<string, string | undefined>)[k];
    const setMilestone = (i: number, patch: Partial<Milestone>) => form.setData('milestones', form.data.milestones.map((x, j) => (j === i ? { ...x, ...patch } : x)));
    const setChapter = (i: number, patch: Partial<Chapter>) => form.setData('chapters', form.data.chapters.map((x, j) => (j === i ? { ...x, ...patch } : x)));

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
                        <a href={fr ? '/fr/our-story' : '/our-story'} target="_blank" rel="noopener">
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
                    <ContentLanguageSwitch value={lang.lang} onChange={lang.setLang} frenchErrors={hasFrenchErrors(form.errors)} />

                    <Panel title="Opening">
                        <div className="space-y-4">
                            <Field label={lang.label('Heading')} name={lang.field('heading')} error={err(lang.field('heading'))} required={!fr} optional={fr}>
                                {(p) => (
                                    <Input
                                        {...p}
                                        value={form.data[lang.field('heading')]}
                                        onChange={(e) => form.setData(lang.field('heading'), e.target.value)}
                                        placeholder={lang.source(form.data.heading)}
                                        maxLength={120}
                                    />
                                )}
                            </Field>
                            <Field label={lang.label('Opening line')} name={lang.field('intro')} error={err(lang.field('intro'))} optional>
                                {(p) => (
                                    <Textarea
                                        {...p}
                                        rows={2}
                                        value={form.data[lang.field('intro')]}
                                        onChange={(e) => form.setData(lang.field('intro'), e.target.value)}
                                        placeholder={lang.source(form.data.intro)}
                                        maxLength={400}
                                    />
                                )}
                            </Field>
                            <Field label={lang.label('Photograph description')} name={lang.field('image_alt')} error={err(lang.field('image_alt'))} required={!fr} optional={fr}>
                                {(p) => (
                                    <Input
                                        {...p}
                                        value={form.data[lang.field('image_alt')]}
                                        onChange={(e) => form.setData(lang.field('image_alt'), e.target.value)}
                                        placeholder={lang.source(form.data.image_alt)}
                                        maxLength={160}
                                    />
                                )}
                            </Field>
                        </div>
                    </Panel>

                    <Panel title="Milestones" description={fr ? 'French place names and texts. Add or remove milestones in English.' : 'Short facts shown as a timeline, in order.'}>
                        <div className="space-y-3">
                            {form.data.milestones.map((m, i) => (
                                <div key={i} className="flex flex-wrap gap-2">
                                    <Input
                                        aria-label={lang.label(`Milestone ${i + 1} place`)}
                                        aria-invalid={err(`milestones.${i}.${lang.field('place')}`) ? true : undefined}
                                        value={fr ? m.place_fr : m.place}
                                        placeholder={fr ? m.place : undefined}
                                        onChange={(e) => setMilestone(i, fr ? { place_fr: e.target.value } : { place: e.target.value })}
                                        className="max-w-40"
                                        maxLength={40}
                                    />
                                    <Input
                                        aria-label={lang.label(`Milestone ${i + 1} text`)}
                                        aria-invalid={err(`milestones.${i}.${lang.field('text')}`) ? true : undefined}
                                        value={fr ? m.text_fr : m.text}
                                        placeholder={fr ? m.text : undefined}
                                        onChange={(e) => setMilestone(i, fr ? { text_fr: e.target.value } : { text: e.target.value })}
                                        className="flex-1"
                                        maxLength={200}
                                    />
                                    {!fr && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Remove milestone ${i + 1}`}
                                            onClick={() => form.setData('milestones', form.data.milestones.filter((_, j) => j !== i))}
                                        >
                                            <Trash2 aria-hidden="true" />
                                        </Button>
                                    )}
                                </div>
                            ))}
                            {!fr && form.data.milestones.length < 6 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => form.setData('milestones', [...form.data.milestones, { place: '', text: '', place_fr: '', text_fr: '' }])}
                                >
                                    <Plus aria-hidden="true" /> Add milestone
                                </Button>
                            )}
                        </div>
                    </Panel>

                    {form.data.chapters.map((c, i) => (
                        <Panel
                            key={i}
                            title={(fr ? c.title_fr || c.title : c.title) || `Chapter ${i + 1}`}
                            actions={
                                !fr &&
                                form.data.chapters.length > 1 && (
                                    <Button type="button" variant="ghost" size="sm" className="text-clay" onClick={() => form.setData('chapters', form.data.chapters.filter((_, j) => j !== i))}>
                                        <Trash2 aria-hidden="true" /> Remove
                                    </Button>
                                )
                            }
                        >
                            <div className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-3">
                                    <Field
                                        label={lang.label('Title')}
                                        name={`chapters.${i}.${lang.field('title')}`}
                                        error={err(`chapters.${i}.${lang.field('title')}`)}
                                        required={!fr}
                                        optional={fr}
                                        className="sm:col-span-2"
                                    >
                                        {(p) => (
                                            <Input
                                                {...p}
                                                value={fr ? c.title_fr : c.title}
                                                placeholder={fr ? c.title : undefined}
                                                onChange={(e) => setChapter(i, fr ? { title_fr: e.target.value } : { title: e.target.value })}
                                                maxLength={80}
                                            />
                                        )}
                                    </Field>
                                    <Field label="Link name" name={`chapters.${i}.key`} error={err(`chapters.${i}.key`)} hint={`/our-story#${c.key || '…'}`} required>
                                        {(p) => <Input {...p} value={c.key} onChange={(e) => setChapter(i, { key: e.target.value })} disabled={fr} maxLength={30} />}
                                    </Field>
                                </div>
                                <Field label={lang.label('Text')} name={`chapters.${i}.${lang.field('body')}`} error={err(`chapters.${i}.${lang.field('body')}`)} required={!fr} optional={fr}>
                                    {(p) => (
                                        <Textarea
                                            {...p}
                                            rows={12}
                                            className="font-serif"
                                            value={fr ? c.body_fr : c.body}
                                            placeholder={fr ? 'Empty: the French page shows the English text.' : undefined}
                                            onChange={(e) => setChapter(i, fr ? { body_fr: e.target.value } : { body: e.target.value })}
                                        />
                                    )}
                                </Field>
                            </div>
                        </Panel>
                    ))}
                    {!fr && form.data.chapters.length < 6 && (
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => form.setData('chapters', [...form.data.chapters, { key: '', title: '', body: '', title_fr: '', body_fr: '' }])}
                        >
                            <Plus aria-hidden="true" /> Add chapter
                        </Button>
                    )}

                    <Panel title="Closing">
                        <div className="space-y-4">
                            <Field label={lang.label('Closing text')} name={lang.field('closing')} error={err(lang.field('closing'))} optional>
                                {(p) => (
                                    <Textarea
                                        {...p}
                                        rows={3}
                                        value={form.data[lang.field('closing')]}
                                        onChange={(e) => form.setData(lang.field('closing'), e.target.value)}
                                        placeholder={lang.source(form.data.closing)}
                                        maxLength={600}
                                    />
                                )}
                            </Field>
                            <Field
                                label={lang.label('Note at the end')}
                                name={lang.field('disclaimer')}
                                error={err(lang.field('disclaimer'))}
                                hint="Keep a sentence saying the story is personal and not medical advice."
                                optional
                            >
                                {(p) => (
                                    <Textarea
                                        {...p}
                                        rows={2}
                                        value={form.data[lang.field('disclaimer')]}
                                        onChange={(e) => form.setData(lang.field('disclaimer'), e.target.value)}
                                        placeholder={lang.source(form.data.disclaimer)}
                                        maxLength={600}
                                    />
                                )}
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
