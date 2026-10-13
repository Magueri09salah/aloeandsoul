import { Link } from '@inertiajs/react';

import type { PostCard as PostCardData, PostDetail } from '@/types';

import { ResponsiveImage } from '@/components/ui/responsive-image';
import { SafeHtml } from '@/components/ui/safe-html';
import { PostCard, PostMeta } from '@/components/store/post-card';
import { useShared } from '@/hooks/use-shared';

interface BlogShowProps {
    post: PostDetail;
    blogHeading: string;
    more: PostCardData[];
}

export default function BlogShow({ post, blogHeading, more }: BlogShowProps) {
    const shareUrl = useShared().seo?.canonical ?? '';

    return (
        <>
            <article className="container-page pt-8 pb-16 lg:pt-12">
                <nav aria-label="Breadcrumb" className="text-sm text-stone">
                    <ol className="flex flex-wrap items-center gap-2">
                        <li>
                            <Link href="/" className="hover:text-ink hover:underline hover:underline-offset-4">
                                Home
                            </Link>
                        </li>
                        <li aria-hidden="true" className="text-line">
                            /
                        </li>
                        <li>
                            <Link href="/blog" className="hover:text-ink hover:underline hover:underline-offset-4">
                                {blogHeading}
                            </Link>
                        </li>
                    </ol>
                </nav>

                <header className="mx-auto mt-10 max-w-3xl text-center">
                    <h1 className="font-display text-display text-balance">{post.title}</h1>
                    <PostMeta post={post} className="mt-5" />
                </header>

                {post.cover && (
                    <div className="mx-auto mt-10 max-w-5xl overflow-hidden bg-sand">
                        <ResponsiveImage
                            image={post.cover}
                            sizes="(min-width: 1024px) 64rem, 100vw"
                            priority
                            className="h-auto"
                        />
                    </div>
                )}

                <div className="mx-auto mt-12 max-w-[44rem]">
                    <SafeHtml html={post.body_html} className="prose-editorial max-w-none" />

                    <footer className="mt-14 flex flex-wrap items-center justify-between gap-4 border-t border-line pt-6 text-sm">
                        <Link href="/blog" className="underline underline-offset-4 hover:text-aloe">
                            All posts
                        </Link>
                        {shareUrl && (
                            <a
                                href={`https://wa.me/?text=${encodeURIComponent(`${post.title} ${shareUrl}`)}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="underline underline-offset-4 hover:text-aloe"
                            >
                                Share on WhatsApp
                            </a>
                        )}
                    </footer>
                </div>
            </article>

            {more.length > 0 && (
                <section aria-labelledby="more-heading" className="container-page border-t border-line pt-16">
                    <h2 id="more-heading" className="font-display text-display-sm">
                        More from the blog
                    </h2>
                    <div className="mt-10 grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                        {more.map((p) => (
                            <PostCard key={p.id} post={p} />
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}
