import { Link } from '@inertiajs/react';

import type { PostCard as PostCardData, PostDetail } from '@/types';

import { ResponsiveImage } from '@/components/ui/responsive-image';
import { SafeHtml } from '@/components/ui/safe-html';
import { PostCard, PostMeta } from '@/components/store/post-card';
import { Accented } from '@/components/store/section-heading';
import { useShared } from '@/hooks/use-shared';
import { useI18n } from '@/lib/i18n';

interface BlogShowProps {
    post: PostDetail;
    blogHeading: string;
    more: PostCardData[];
}

export default function BlogShow({ post, blogHeading, more }: BlogShowProps) {
    const shareUrl = useShared().seo?.canonical ?? '';
    const { t, to } = useI18n();

    return (
        <>
            <article className="container-page pt-8 pb-16 lg:pt-12">
                <nav aria-label={t('Breadcrumb')} className="text-sm text-stone">
                    <ol className="flex flex-wrap items-center gap-2">
                        <li>
                            <Link href={to('/')} className="hover:text-ink hover:underline hover:underline-offset-4">
                                {t('Home')}
                            </Link>
                        </li>
                        <li aria-hidden="true" className="text-line">
                            /
                        </li>
                        <li>
                            <Link href={to('/blog')} className="hover:text-ink hover:underline hover:underline-offset-4">
                                {blogHeading}
                            </Link>
                        </li>
                    </ol>
                </nav>

                {post.cover ? (
                    // The cover as a banner, with the title over it.
                    <header className="relative isolate mt-6 overflow-hidden rounded-3xl bg-aloe">
                        <ResponsiveImage
                            image={post.cover}
                            sizes="(min-width: 1320px) 1240px, 100vw"
                            priority
                            className="absolute inset-0 -z-10"
                        />
                        <div className="absolute inset-0 -z-10 bg-ink/45" aria-hidden="true" />
                        <div className="flex min-h-[20rem] flex-col items-center justify-center px-6 py-14 text-center text-white sm:min-h-[26rem] lg:px-20">
                            <h1 className="max-w-4xl font-display text-display text-balance">{post.title}</h1>
                            <PostMeta post={post} className="mt-5 text-white/90" />
                        </div>
                    </header>
                ) : (
                    <header className="mx-auto mt-10 max-w-3xl text-center">
                        <h1 className="font-display text-display text-balance">{post.title}</h1>
                        <PostMeta post={post} className="mt-5" />
                    </header>
                )}

                <div className="mx-auto mt-12 max-w-[46rem] lg:mt-16">
                    <SafeHtml html={post.body_html} className="prose-editorial max-w-none" />

                    <footer className="mt-14 flex flex-wrap items-center justify-between gap-4 border-t border-line pt-6 text-sm font-semibold tracking-[0.03em] uppercase">
                        <Link href={to('/blog')} className="underline underline-offset-4 hover:text-dune-deep">
                            {t('All posts')}
                        </Link>
                        {shareUrl && (
                            <a
                                href={`https://wa.me/?text=${encodeURIComponent(`${post.title} ${shareUrl}`)}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="underline underline-offset-4 hover:text-dune-deep"
                            >
                                {t('Share on WhatsApp')}
                            </a>
                        )}
                    </footer>
                </div>
            </article>

            {more.length > 0 && (
                <section aria-labelledby="more-heading" className="container-page border-t border-line pt-16">
                    <h2 id="more-heading" className="font-display text-display-sm">
                        <Accented text={t('More from the blog')} />
                    </h2>
                    <div className="mt-10 grid gap-x-6 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                        {more.map((p) => (
                            <PostCard key={p.id} post={p} />
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}
