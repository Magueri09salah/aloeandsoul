import { Link } from '@inertiajs/react';
import { Banknote, PackageSearch, UserRoundCheck } from 'lucide-react';

import type { Collaborator, ImageData, Motif as MotifName, PostCard as PostCardData, ProductCard as ProductCardData } from '@/types';

import { Motif } from '@/components/brand/motif';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { ResponsiveImage } from '@/components/ui/responsive-image';
import { Collaborators } from '@/components/store/collaborators';
import { PostCard } from '@/components/store/post-card';
import { ProductCard } from '@/components/store/product-card';
import { SectionHeading } from '@/components/store/section-heading';
import { cn } from '@/lib/utils';

interface HomeProps {
    hero: { heading: string; text: string; attribution: string; image: ImageData };
    intro: { heading: string; text: string };
    featuredHeading: string;
    featured: ProductCardData[];
    bestsellers: ProductCardData[];
    categories: { name: string; slug: string; tagline: string | null; tint: string; motif: MotifName; product_count: number }[];
    ritual: { heading: string; intro: string; steps: { note: string; product: ProductCardData }[] } | null;
    journeys: { key: string; title: string; excerpt: string }[];
    storyImage: ImageData;
    faqs: { id: number; question: string; answer: string }[];
    collaborators: { heading: string; intro: string; items: Collaborator[] } | null;
    latestPosts: { heading: string; items: PostCardData[] } | null;
}

export default function Home({ hero, intro, featuredHeading, featured, bestsellers, categories, ritual, journeys, faqs, collaborators, latestPosts }: HomeProps) {
    return (
        <>
            {/* Hero: the founder in the riad arch — the page's one bold gesture. */}
            <section className="container-page grid items-center gap-10 pt-10 pb-16 lg:grid-cols-12 lg:gap-8 lg:pt-16 lg:pb-24">
                <div className="lg:col-span-7">
                    <h1 className="font-display text-display-xl text-balance">{hero.heading}</h1>
                    {hero.text && (
                        <figure className="mt-8 max-w-xl border-l border-dune pl-6">
                            <blockquote className="font-serif text-xl leading-relaxed text-ink italic sm:text-[1.35rem]">
                                {hero.text}
                            </blockquote>
                            {hero.attribution && <figcaption className="mt-3 text-sm text-stone">{hero.attribution}</figcaption>}
                        </figure>
                    )}
                    <div className="mt-10 flex flex-wrap items-center gap-x-6 gap-y-4">
                        <Button asChild size="lg">
                            <Link href="/shop">Shop the collection</Link>
                        </Button>
                        <Link href="/our-story" className="text-[0.95rem] font-medium underline underline-offset-4 hover:text-aloe">
                            Read our story
                        </Link>
                    </div>
                </div>
                <div className="mx-auto w-full max-w-sm sm:max-w-md lg:col-span-5 lg:max-w-none">
                    <div className="arch aspect-[3/4] bg-sand motion-safe:animate-arch-reveal">
                        <ResponsiveImage image={hero.image} sizes="(min-width: 1024px) 40vw, (min-width: 640px) 28rem, 90vw" priority />
                    </div>
                </div>
            </section>

            {/* What ordering here means, stated plainly. */}
            <section aria-label="How ordering works" className="border-y border-line bg-white/60">
                <ul className="container-page grid gap-6 py-8 sm:grid-cols-3">
                    {[
                        { icon: Banknote, title: 'Pay in cash on delivery', text: 'No card or online payment needed.' },
                        { icon: UserRoundCheck, title: 'No account needed', text: 'Order with your name, phone and address.' },
                        { icon: PackageSearch, title: 'Follow your order', text: 'Use your order reference to check its status.' },
                    ].map(({ icon: Icon, title, text }) => (
                        <li key={title} className="flex items-start gap-4">
                            <Icon className="mt-0.5 size-6 shrink-0 text-dune" strokeWidth={1.5} aria-hidden="true" />
                            <div>
                                <p className="font-medium">{title}</p>
                                <p className="text-sm text-stone">{text}</p>
                            </div>
                        </li>
                    ))}
                </ul>
            </section>

            {/* Philosophy */}
            {(intro.heading || intro.text) && (
                <section className="container-page py-20 lg:py-28">
                    <div className="grid gap-6 lg:grid-cols-12">
                        <h2 className="font-display text-display text-balance lg:col-span-7">{intro.heading}</h2>
                        {intro.text && <p className="font-serif text-editorial text-stone lg:col-span-4 lg:col-start-9 lg:self-end">{intro.text}</p>}
                    </div>
                </section>
            )}

            {/* Categories: staggered tiles rather than a uniform card grid. */}
            {categories.length > 0 && (
                <section aria-labelledby="categories-heading" className="container-page pb-20 lg:pb-28">
                    <h2 id="categories-heading" className="sr-only">
                        Shop by category
                    </h2>
                    <ul className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4 lg:gap-5">
                        {categories.map((c, i) => (
                            <li key={c.slug} className={cn(i % 2 === 1 && 'lg:translate-y-12')}>
                                <Link
                                    href={`/shop/${c.slug}`}
                                    className="group flex aspect-[3/4] flex-col justify-between p-5 transition-colors sm:p-7"
                                    style={{ backgroundColor: c.tint }}
                                >
                                    <span className="block w-14 text-dune transition-transform duration-500 ease-(--ease-out-soft) motion-safe:group-hover:-translate-y-1 sm:w-20">
                                        <Motif name={c.motif} />
                                    </span>
                                    <span>
                                        <span className="block font-display text-2xl leading-tight sm:text-3xl">{c.name}</span>
                                        {c.tagline && <span className="mt-2 hidden text-sm text-stone sm:block">{c.tagline}</span>}
                                        <span className="mt-3 inline-block text-sm font-medium underline-offset-4 group-hover:underline">
                                            {c.product_count > 0 ? `${c.product_count} products` : 'Coming soon'}
                                        </span>
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {featured.length > 0 && (
                <section className="container-page pt-8 pb-20 lg:pt-16">
                    <SectionHeading title={featuredHeading} link={{ href: '/shop', label: 'See all products' }} />
                    <div className="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                        {featured.map((p) => (
                            <ProductCard key={p.id} product={p} />
                        ))}
                    </div>
                </section>
            )}

            {ritual && (
                <section className="bg-sand py-20 lg:py-28">
                    <div className="container-page">
                        <SectionHeading title={ritual.heading} intro={ritual.intro || undefined} />
                        <ol className="mt-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                            {ritual.steps.map((step, i) => (
                                <li key={step.product.id}>
                                    <p className="mb-4 flex items-baseline gap-3">
                                        <span className="font-display text-4xl text-dune">{i + 1}</span>
                                        {step.note && <span className="text-sm text-stone">{step.note}</span>}
                                    </p>
                                    <ProductCard product={step.product} />
                                </li>
                            ))}
                        </ol>
                    </div>
                </section>
            )}

            {bestsellers.length > 0 && (
                <section className="container-page py-20">
                    <SectionHeading title="Bestsellers" link={{ href: '/shop', label: 'Browse the shop' }} />
                    <div className="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                        {bestsellers.map((p) => (
                            <ProductCard key={p.id} product={p} />
                        ))}
                    </div>
                </section>
            )}

            {/* Founder story preview in her own words. */}
            <section className="bg-sand py-20 lg:py-28">
                <div className="container-page grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-5">
                        <h2 className="font-display text-display text-balance">From London to Marrakech</h2>
                        <p className="mt-6 font-serif text-editorial italic">“I wasn’t looking for a business. I was simply looking for answers.”</p>
                        <Button asChild variant="secondary" className="mt-8">
                            <Link href="/our-story">Read the founder’s story</Link>
                        </Button>
                    </div>
                    <ul className="grid gap-8 sm:grid-cols-3 lg:col-span-6 lg:col-start-7 lg:grid-cols-1 lg:gap-0 lg:divide-y lg:divide-dune/40">
                        {journeys.map((j) => (
                            <li key={j.key} className="lg:py-7 lg:first:pt-0">
                                <Link href={`/our-story#${j.key}`} className="group block">
                                    <h3 className="font-display text-2xl group-hover:text-aloe">{j.title}</h3>
                                    <p className="mt-2 font-serif text-stone">{j.excerpt}</p>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {collaborators && <Collaborators heading={collaborators.heading} intro={collaborators.intro} items={collaborators.items} />}

            {latestPosts && latestPosts.items.length > 0 && (
                <section className="container-page pt-20 lg:pt-28">
                    <SectionHeading title={latestPosts.heading} link={{ href: '/blog', label: 'All posts' }} />
                    <div className="mt-10 grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                        {latestPosts.items.map((post) => (
                            <PostCard key={post.id} post={post} />
                        ))}
                    </div>
                </section>
            )}

            {faqs.length > 0 && (
                <section className="container-page grid gap-10 pt-20 lg:grid-cols-12 lg:pt-28">
                    <div className="lg:col-span-4">
                        <h2 className="font-display text-display-sm">Questions before you order</h2>
                        <Link href="/faq" className="mt-4 inline-block font-medium underline underline-offset-4 hover:text-aloe">
                            All questions
                        </Link>
                    </div>
                    <Accordion type="single" collapsible className="border-t border-line lg:col-span-7 lg:col-start-6">
                        {faqs.map((f) => (
                            <AccordionItem key={f.id} value={String(f.id)}>
                                <AccordionTrigger>{f.question}</AccordionTrigger>
                                <AccordionContent className="max-w-prose whitespace-pre-line">{f.answer}</AccordionContent>
                            </AccordionItem>
                        ))}
                    </Accordion>
                </section>
            )}
        </>
    );
}
