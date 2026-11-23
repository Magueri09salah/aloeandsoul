import { Link } from '@inertiajs/react';

import type { PostCard as PostCardData } from '@/types';

import { Motif } from '@/components/brand/motif';
import { ResponsiveImage } from '@/components/ui/responsive-image';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** Date and reading time, e.g. "13 Oct 2026, 4 min read". */
export function PostMeta({ post, className }: { post: Pick<PostCardData, 'published_at' | 'reading_minutes'>; className?: string }) {
    const { t, date } = useI18n();
    return (
        <p className={cn('text-xs font-semibold tracking-[0.06em] text-stone uppercase', className)}>
            <time dateTime={post.published_at ?? undefined}>{date(post.published_at)}</time>, {t(':count min read', { count: post.reading_minutes })}
        </p>
    );
}

export function PostCover({ post, sizes, priority = false, className }: { post: PostCardData; sizes: string; priority?: boolean; className?: string }) {
    return (
        <div className={cn('overflow-hidden rounded-2xl bg-sand', className)}>
            {post.cover ? (
                <ResponsiveImage
                    image={post.cover}
                    sizes={sizes}
                    priority={priority}
                    alt=""
                    className="transition-transform duration-700 ease-(--ease-out-soft) motion-safe:group-hover:scale-[1.04]"
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

/** Journal card: rounded photo, date, title, excerpt and "Read more". */
export function PostCard({ post, priority = false, headingLevel: Heading = 'h3' }: { post: PostCardData; priority?: boolean; headingLevel?: 'h2' | 'h3' }) {
    const { t, to } = useI18n();
    const href = to(`/blog/${post.slug}`);

    return (
        <article className="group flex flex-col">
            <Link href={href} className="block" tabIndex={-1} aria-hidden="true">
                <PostCover post={post} sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw" priority={priority} className="aspect-[5/4]" />
            </Link>
            <PostMeta post={post} className="mt-6" />
            <Heading className="mt-2.5 text-xl leading-snug font-semibold text-ink">
                <Link href={href} className="hover:text-dune-deep">
                    {post.title}
                </Link>
            </Heading>
            {post.excerpt && <p className="mt-3 line-clamp-3 text-stone">{post.excerpt}</p>}
            <Link href={href} className="mt-5 self-start text-sm font-semibold tracking-[0.03em] uppercase underline underline-offset-4 hover:text-dune-deep">
                {t('Read more')}
                <span className="sr-only">: {post.title}</span>
            </Link>
        </article>
    );
}
