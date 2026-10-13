import { Link } from '@inertiajs/react';

import type { PostCard as PostCardData } from '@/types';

import { Motif } from '@/components/brand/motif';
import { ResponsiveImage } from '@/components/ui/responsive-image';
import { useShared } from '@/hooks/use-shared';
import { formatDate } from '@/lib/date';
import { cn } from '@/lib/utils';

/** Date and reading time, e.g. "13 Oct 2026, 4 min read". */
export function PostMeta({ post, className }: { post: Pick<PostCardData, 'published_at' | 'reading_minutes'>; className?: string }) {
    const { store } = useShared();
    return (
        <p className={cn('text-sm text-stone', className)}>
            <time dateTime={post.published_at ?? undefined}>{formatDate(post.published_at, store.timezone)}</time>, {post.reading_minutes} min read
        </p>
    );
}

export function PostCover({ post, sizes, priority = false, className }: { post: PostCardData; sizes: string; priority?: boolean; className?: string }) {
    return (
        <div className={cn('overflow-hidden bg-sand', className)}>
            {post.cover ? (
                <ResponsiveImage
                    image={post.cover}
                    sizes={sizes}
                    priority={priority}
                    alt=""
                    className="transition-transform duration-700 ease-(--ease-out-soft) motion-safe:group-hover:scale-[1.03]"
                />
            ) : (
                <div className="flex h-full w-full items-center justify-center text-dune">
                    <div className="w-1/4 max-w-24">
                        <Motif name="leaf" />
                    </div>
                </div>
            )}
        </div>
    );
}

export function PostCard({ post, featured = false }: { post: PostCardData; featured?: boolean }) {
    return (
        <article className={cn('group', featured && 'grid gap-6 md:grid-cols-12 md:items-center md:gap-10')}>
            <Link href={`/blog/${post.slug}`} className={cn('block', featured && 'md:col-span-7')} tabIndex={-1} aria-hidden="true">
                <PostCover
                    post={post}
                    sizes={featured ? '(min-width: 768px) 58vw, 100vw' : '(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw'}
                    priority={featured}
                    className={featured ? 'aspect-[16/10]' : 'aspect-[4/3]'}
                />
            </Link>
            <div className={cn(featured ? 'md:col-span-5' : 'mt-4')}>
                <PostMeta post={post} />
                <h2 className={cn('mt-2 font-display leading-tight', featured ? 'text-display-sm' : 'text-2xl')}>
                    <Link href={`/blog/${post.slug}`} className="hover:text-aloe">
                        {post.title}
                    </Link>
                </h2>
                <p className={cn('mt-3 text-stone', featured && 'text-lg')}>{post.excerpt}</p>
                <Link href={`/blog/${post.slug}`} className="mt-4 inline-block text-sm font-medium underline underline-offset-4 hover:text-aloe">
                    Read the post<span className="sr-only">: {post.title}</span>
                </Link>
            </div>
        </article>
    );
}
