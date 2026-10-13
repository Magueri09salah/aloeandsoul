import { Link } from '@inertiajs/react';

import type { ImageData } from '@/types';

import { Button } from '@/components/ui/button';
import { ResponsiveImage } from '@/components/ui/responsive-image';

interface StoryProps {
    story: {
        heading: string;
        intro: string;
        image: ImageData;
        milestones: { place: string; text: string }[];
        chapters: { key: string; title: string; paragraphs: string[] }[];
        closing: string;
        disclaimer: string;
        tagline: string;
    };
}

/** The founder's own words, set as a long-form editorial feature. */
export default function Story({ story }: StoryProps) {
    return (
        <article>
            <header className="container-page grid items-end gap-10 pt-10 pb-16 lg:grid-cols-12 lg:gap-8 lg:pt-16 lg:pb-24">
                <div className="lg:col-span-7">
                    <h1 className="font-display text-display-xl text-balance">{story.heading}</h1>
                    {story.intro && (
                        <p className="mt-8 max-w-xl border-l border-dune pl-6 font-serif text-xl leading-relaxed italic sm:text-2xl">{story.intro}</p>
                    )}
                </div>
                <div className="mx-auto w-full max-w-sm lg:col-span-4 lg:col-start-9 lg:max-w-none">
                    <div className="arch aspect-[3/4] bg-sand">
                        <ResponsiveImage image={story.image} sizes="(min-width: 1024px) 30vw, 90vw" priority />
                    </div>
                </div>
            </header>

            {story.milestones.length > 0 && (
                <section aria-label="Journey" className="bg-sand">
                    <ol className="container-page grid gap-8 py-12 sm:grid-cols-3 sm:gap-6">
                        {story.milestones.map((m, i) => (
                            <li key={m.place} className="relative sm:pt-8">
                                <span aria-hidden="true" className="absolute top-3 right-0 left-0 hidden h-px bg-dune/50 sm:block" />
                                <span aria-hidden="true" className="absolute top-1.5 left-0 hidden size-3 rounded-full border border-dune bg-ivory sm:block" />
                                <p className="font-display text-2xl">
                                    <span className="sr-only">Step {i + 1}: </span>
                                    {m.place}
                                </p>
                                <p className="mt-2 max-w-xs text-stone">{m.text}</p>
                            </li>
                        ))}
                    </ol>
                </section>
            )}

            {story.chapters.map((chapter) => (
                <section key={chapter.key} id={chapter.key} aria-labelledby={`${chapter.key}-title`} className="container-page grid gap-8 border-b border-line py-16 lg:grid-cols-12 lg:py-24">
                    <div className="lg:col-span-4">
                        <h2 id={`${chapter.key}-title`} className="font-display text-display-sm lg:sticky lg:top-28">
                            {chapter.title}
                        </h2>
                    </div>
                    <div className="prose-editorial lg:col-span-7 lg:col-start-6">
                        {chapter.paragraphs.map((p, i) => (
                            <p key={i} className={i === 0 ? 'font-display text-[1.6rem] leading-snug sm:text-[1.85rem]' : undefined}>
                                {p}
                            </p>
                        ))}
                    </div>
                </section>
            ))}

            <section className="container-page py-20 text-center lg:py-28">
                <p className="mx-auto max-w-4xl font-display text-display text-balance">{story.tagline}</p>
                {story.closing && <p className="mx-auto mt-6 max-w-2xl font-serif text-editorial text-stone">{story.closing}</p>}
                <div className="mt-10 flex flex-wrap justify-center gap-3">
                    <Button asChild size="lg">
                        <Link href="/shop">Visit the shop</Link>
                    </Button>
                    <Button asChild size="lg" variant="secondary">
                        <Link href="/contact">Get in touch</Link>
                    </Button>
                </div>
                {story.disclaimer && <p className="mx-auto mt-16 max-w-2xl text-sm text-stone">{story.disclaimer}</p>}
            </section>
        </article>
    );
}
