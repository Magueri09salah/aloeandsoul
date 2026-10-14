import { Link } from '@inertiajs/react';

import type { PostCard as PostCardData } from '@/types';

import { PostCard } from '@/components/store/post-card';
import { useI18n } from '@/lib/i18n';

interface BlogIndexProps {
    heading: string;
    intro: string;
    posts: { data: PostCardData[]; current_page: number; last_page: number; prev: string | null; next: string | null };
}

export default function BlogIndex({ heading, intro, posts }: BlogIndexProps) {
    const [first, ...rest] = posts.data;
    const isFirstPage = posts.current_page === 1;
    const { t } = useI18n();

    return (
        <div className="container-page py-12 lg:py-20">
            <header className="max-w-2xl">
                <h1 className="font-display text-display">{heading}</h1>
                {intro && <p className="mt-4 text-lg text-stone">{intro}</p>}
            </header>

            {posts.data.length === 0 ? (
                <p className="mt-16 text-lg text-stone">{t('The first posts are on their way. Please come back soon.')}</p>
            ) : (
                <>
                    {isFirstPage && first && (
                        <div className="mt-12 border-t border-line pt-12">
                            <PostCard post={first} featured />
                        </div>
                    )}
                    <div className="mt-16 grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                        {(isFirstPage ? rest : posts.data).map((post) => (
                            <PostCard key={post.id} post={post} />
                        ))}
                    </div>
                </>
            )}

            {posts.last_page > 1 && (
                <nav aria-label={t('Pages')} className="mt-16 flex justify-between border-t border-line pt-6">
                    {posts.prev ? (
                        <Link href={posts.prev} rel="prev" className="underline underline-offset-4 hover:text-aloe">
                            {t('Newer posts')}
                        </Link>
                    ) : (
                        <span />
                    )}
                    {posts.next && (
                        <Link href={posts.next} rel="next" className="underline underline-offset-4 hover:text-aloe">
                            {t('Older posts')}
                        </Link>
                    )}
                </nav>
            )}
        </div>
    );
}
