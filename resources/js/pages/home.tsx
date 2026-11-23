import { Link } from '@inertiajs/react';
import { ArrowRight, Banknote, PackageSearch, UserRoundCheck } from 'lucide-react';

import type {
    Collaborator,
    ImageData,
    Motif as MotifName,
    PostCard as PostCardData,
    ProductCard as ProductCardData,
} from '@/types';

import { Motif } from '@/components/brand/motif';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { ResponsiveImage } from '@/components/ui/responsive-image';
import { Collaborators } from '@/components/store/collaborators';
import { PostCard } from '@/components/store/post-card';
import { ProductRail } from '@/components/store/product-rail';
import { Accented, SectionHeading } from '@/components/store/section-heading';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface HomeProps {
    hero: { heading: string; text: string; attribution: string; image: ImageData };
    intro: { heading: string; text: string };
    featuredHeading: string;
    featured: ProductCardData[];
    bestsellers: ProductCardData[];
    categories: {
        name: string;
        slug: string;
        tagline: string | null;
        tint: string;
        motif: MotifName;
        product_count: number;
    }[];
    ritual: { heading: string; intro: string; steps: { note: string; product: ProductCardData }[] } | null;
    journeys: { key: string; title: string; excerpt: string }[];
    storyImage: ImageData;
    faqs: { id: number; question: string; answer: string }[];
    collaborators: { heading: string; intro: string; items: Collaborator[] } | null;
    latestPosts: { heading: string; items: PostCardData[] } | null;
}

export default function Home({
    hero,
    intro,
    featuredHeading,
    featured,
    bestsellers,
    categories,
    ritual,
    journeys,
    storyImage,
    faqs,
    collaborators,
    latestPosts,
}: HomeProps) {
    const { t, tc, to } = useI18n();
    // The story photo is shown only when it differs from the hero's.
    const storyPhoto = storyImage.src !== hero.image.src;

    return (
        <>
            {/* Hero: full-width band in the green of the logo's aloe, the words on one half and the founder's photograph on the other. */}
            <section className="bg-leaf text-white">
                <div className="grid lg:min-h-[min(44rem,calc(100svh-10rem))] lg:grid-cols-2">
                    <div className="relative aspect-[4/5] max-h-[70svh] w-full sm:aspect-[16/11] lg:order-2 lg:aspect-auto lg:max-h-none">
                        <ResponsiveImage
                            image={hero.image}
                            sizes="(min-width: 1024px) 50vw, 100vw"
                            priority
                            className="absolute inset-0 object-[50%_30%]"
                        />
                    </div>
                    <div className="flex items-center">
                        <div className="w-full px-4 py-12 sm:px-6 lg:py-20 lg:pr-14 lg:pl-[max(2.5rem,calc((100vw-1320px)/2+2.5rem))]">
                            <h1 className="max-w-xl font-display text-display text-balance">
                                <Accented text={hero.heading} />
                            </h1>
                            {hero.text && (
                                <figure className="mt-7 max-w-lg">
                                    <blockquote className="text-lg leading-relaxed sm:text-xl">{hero.text}</blockquote>
                                    {hero.attribution && (
                                        <figcaption className="mt-3 text-sm font-semibold tracking-[0.04em] uppercase">
                                            {hero.attribution}
                                        </figcaption>
                                    )}
                                </figure>
                            )}
                            <div className="mt-9 flex flex-wrap items-center gap-x-7 gap-y-4">
                                <Button
                                    asChild
                                    size="lg"
                                    wrap
                                    className="bg-white text-ink hover:bg-ivory focus-visible:outline-white max-sm:px-6"
                                >
                                    <Link href={to('/shop')}>
                                        {t('Shop the collection')}
                                        <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                                <Link
                                    href={to('/our-story')}
                                    className="text-sm font-semibold tracking-[0.03em] uppercase underline underline-offset-4 hover:decoration-2 focus-visible:outline-white"
                                >
                                    {t('Read our story')}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Categories as round tiles. */}
            {categories.length > 0 && (
                <section aria-labelledby="categories-heading" className="container-page pt-16 pb-6 lg:pt-24">
                    <SectionHeading id="categories-heading" title={t('Shop by category')} />
                    <ul className="-mx-4 mt-10 no-scrollbar flex gap-x-5 overflow-x-auto px-4 sm:mx-0 sm:grid sm:grid-cols-4 sm:gap-x-6 sm:overflow-visible sm:px-0">
                        {categories.map((c) => (
                            <li key={c.slug} className="w-32 shrink-0 sm:w-auto">
                                <Link
                                    href={to(`/shop/${c.slug}`)}
                                    className="group flex flex-col items-center text-center"
                                >
                                    <span
                                        className="flex aspect-square w-full max-w-48 items-center justify-center rounded-full transition-transform duration-500 ease-(--ease-out-soft) motion-safe:group-hover:scale-[1.03]"
                                        style={{ backgroundColor: c.tint }}
                                    >
                                        <span className="block w-2/5 text-dune">
                                            <Motif name={c.motif} animated />
                                        </span>
                                    </span>
                                    <span className="mt-4 text-[1.05rem] text-ink group-hover:underline group-hover:underline-offset-4">
                                        {c.name}
                                    </span>
                                    <span className="mt-1 text-sm text-stone">
                                        {c.product_count > 0
                                            ? tc(':count product|:count products', c.product_count)
                                            : t('Coming soon')}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {/* What ordering here means, stated plainly. */}
            <section aria-labelledby="ordering-heading" className="container-page py-14 lg:py-20">
                <div className="grid gap-10 border-y border-line py-10 lg:grid-cols-12 lg:items-center lg:gap-8 lg:py-12">
                    <h2 id="ordering-heading" className="font-display text-display-sm text-balance lg:col-span-3">
                        <Accented text={t('How ordering works')} />
                    </h2>
                    <ul className="grid gap-8 sm:grid-cols-3 sm:gap-0 sm:divide-x sm:divide-line lg:col-span-9">
                        {[
                            {
                                icon: Banknote,
                                title: t('Pay in cash on delivery'),
                                text: t('No card or online payment needed.'),
                            },
                            {
                                icon: UserRoundCheck,
                                title: t('No account needed'),
                                text: t('Order with your name, phone and address.'),
                            },
                            {
                                icon: PackageSearch,
                                title: t('Follow your order'),
                                text: t('Use your order reference to check its status.'),
                            },
                        ].map(({ icon: Icon, title, text }) => (
                            <li key={title} className="sm:px-6 sm:first:pl-0 lg:px-8 lg:first:pl-8">
                                <span className="flex size-14 items-center justify-center rounded-full border border-line bg-white text-dune-deep">
                                    <Icon className="size-6" strokeWidth={1.5} aria-hidden="true" />
                                </span>
                                <p className="mt-5 font-semibold text-ink">{title}</p>
                                <p className="mt-1 text-[0.95rem] text-stone">{text}</p>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {featured.length > 0 && (
                <ProductRail
                    id="featured-heading"
                    title={featuredHeading}
                    products={featured}
                    link={{ href: to('/shop'), label: t('Shop the collection') }}
                />
            )}

            {ritual && (
                <ProductRail
                    id="ritual-heading"
                    title={ritual.heading}
                    intro={ritual.intro || null}
                    products={ritual.steps.map((s) => s.product)}
                    notes={ritual.steps.map((step, i) => (
                        <p key={step.product.id} className="mb-3 flex min-h-10 items-baseline gap-3">
                            <span className="font-display text-3xl text-dune">{i + 1}</span>
                            {step.note && <span className="text-sm text-stone">{step.note}</span>}
                        </p>
                    ))}
                    className="bg-panel"
                />
            )}

            {bestsellers.length > 0 && (
                <ProductRail
                    id="bestsellers-heading"
                    title={t('Bestsellers')}
                    products={bestsellers}
                    link={{ href: to('/shop'), label: t('Browse the shop') }}
                />
            )}

            {/* Philosophy: heading on the left, words on the right. */}
            {(intro.heading || intro.text) && (
                <section className="container-page py-16 lg:py-24">
                    <div className="grid gap-8 lg:grid-cols-12">
                        <h2 className="font-display text-display text-balance lg:col-span-6">
                            <Accented text={intro.heading} className="text-dune" />
                        </h2>
                        <div className="lg:col-span-5 lg:col-start-8 lg:self-end">
                            {intro.text && <p className="text-[1.05rem] leading-relaxed text-stone">{intro.text}</p>}
                            <Link
                                href={to('/our-story')}
                                className="group mt-6 inline-flex items-center gap-3 text-sm font-semibold tracking-[0.08em] text-dune-deep uppercase"
                            >
                                {t('Our story')}
                                <span className="inline-flex size-8 items-center justify-center rounded-full border border-dune-deep transition-colors group-hover:bg-dune-deep group-hover:text-white">
                                    <ArrowRight className="size-4" aria-hidden="true" />
                                </span>
                            </Link>
                        </div>
                    </div>
                </section>
            )}

            {/* Founder story preview in her own words. */}
            <section aria-labelledby="story-heading" className="container-page">
                <div className="grid overflow-hidden rounded-3xl bg-sand lg:grid-cols-2">
                    {storyPhoto && (
                        <div className="relative aspect-[4/3] lg:aspect-auto lg:min-h-[34rem]">
                            <ResponsiveImage
                                image={storyImage}
                                sizes="(min-width: 1024px) 40vw, 100vw"
                                className="absolute inset-0"
                            />
                        </div>
                    )}
                    <div
                        className={
                            storyPhoto
                                ? 'p-8 sm:p-12 lg:p-16'
                                : 'p-8 sm:p-12 lg:col-span-2 lg:grid lg:grid-cols-2 lg:gap-16 lg:p-16'
                        }
                    >
                        <div>
                            <h2 id="story-heading" className="font-display text-display-sm text-balance">
                                <Accented text={t('From London to Marrakech')} className="text-dune-deep" />
                            </h2>
                            <p className="mt-5 font-display text-2xl leading-snug italic">
                                {t('“I wasn’t looking for a business. I was simply looking for answers.”')}
                            </p>
                            <Button asChild size="lg" wrap className="mt-8">
                                <Link href={to('/our-story')}>{t('Read the founder’s story')}</Link>
                            </Button>
                        </div>
                        <ul
                            className={cn(
                                'mt-10 divide-y divide-dune/30 border-t border-dune/30',
                                !storyPhoto && 'lg:mt-0 lg:border-t-0',
                            )}
                        >
                            {journeys.map((j) => (
                                <li key={j.key} className={cn('py-6', !storyPhoto && 'lg:first:pt-0')}>
                                    <Link href={to(`/our-story#${j.key}`)} className="group block">
                                        <h3 className="font-display text-2xl group-hover:text-dune-deep">{j.title}</h3>
                                        <p className="mt-2 text-stone">{j.excerpt}</p>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </section>

            {latestPosts && latestPosts.items.length > 0 && (
                <section aria-labelledby="journal-heading" className="container-page pt-20 lg:pt-28">
                    <SectionHeading
                        id="journal-heading"
                        title={latestPosts.heading}
                        link={{ href: to('/blog'), label: t('All posts') }}
                    />
                    <div className="mt-10 grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                        {latestPosts.items.map((post) => (
                            <PostCard key={post.id} post={post} />
                        ))}
                    </div>
                </section>
            )}

            {collaborators && (
                <Collaborators
                    heading={collaborators.heading}
                    intro={collaborators.intro}
                    items={collaborators.items}
                />
            )}

            {faqs.length > 0 && (
                <section className="container-page grid gap-10 pt-20 lg:grid-cols-12 lg:pt-28">
                    <div className="lg:col-span-4">
                        <h2 className="font-display text-display-sm text-balance">
                            <Accented text={t('Questions before you order')} />
                        </h2>
                        <Link
                            href={to('/faq')}
                            className="mt-5 inline-block text-sm font-semibold tracking-[0.03em] uppercase underline underline-offset-4 hover:text-dune-deep"
                        >
                            {t('All questions')}
                        </Link>
                    </div>
                    <Accordion type="single" collapsible className="border-t border-line lg:col-span-7 lg:col-start-6">
                        {faqs.map((f) => (
                            <AccordionItem key={f.id} value={String(f.id)}>
                                <AccordionTrigger>{f.question}</AccordionTrigger>
                                <AccordionContent className="max-w-prose whitespace-pre-line">
                                    {f.answer}
                                </AccordionContent>
                            </AccordionItem>
                        ))}
                    </Accordion>
                </section>
            )}
        </>
    );
}
