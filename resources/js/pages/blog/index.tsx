import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';

import type { PostCard as PostCardData } from '@/types';

import { Button } from '@/components/ui/button';
import { PostCard } from '@/components/store/post-card';
import { Accented } from '@/components/store/section-heading';
import { useI18n } from '@/lib/i18n';

interface BlogIndexProps {
    heading: string;
    intro: string;
    posts: { data: PostCardData[]; current_page: number; last_page: number; prev: string | null; next: string | null };
}

export default function BlogIndex({ heading, intro, posts }: BlogIndexProps) {
    const { t } = useI18n();

    return (
        <>
            {/* Banner: sand ripples of the Essaouira dunes (from the founder's photograph), behind the title. */}
            <header className="relative isolate overflow-hidden bg-aloe">
                <img
                    src="/brand/dunes-banner-1200.webp"
                    width={1200}
                    height={175}
                    alt=""
                    className="absolute inset-0 -z-10 h-full w-full object-cover"
                />
                <div className="absolute inset-0 -z-10 bg-ink/45" aria-hidden="true" />
                <div className="container-page flex min-h-[16rem] flex-col items-center justify-center py-14 text-center text-white sm:min-h-[18rem]">
                    <h1 className="font-display text-display text-balance">
                        <Accented text={heading} />
                    </h1>
                    {intro && <p className="mt-4 max-w-xl text-lg text-white/90">{intro}</p>}
                </div>
            </header>

            <div className="container-page py-12 lg:py-16">
                {posts.data.length === 0 ? (
                    <p className="py-10 text-center text-lg text-stone">{t('The first posts are on their way. Please come back soon.')}</p>
                ) : (
                    <div className="grid gap-x-6 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                        {posts.data.map((post, i) => (
                            <PostCard key={post.id} post={post} priority={i < 3} headingLevel="h2" />
                        ))}
                    </div>
                )}

                {posts.last_page > 1 && (
                    <nav aria-label={t('Pages')} className="mt-16 flex flex-wrap items-center justify-between gap-4 border-t border-line pt-8">
                        {posts.prev ? (
                            <Button asChild variant="secondary">
                                <Link href={posts.prev} rel="prev">
                                    <ArrowLeft aria-hidden="true" />
                                    {t('Newer posts')}
                                </Link>
                            </Button>
                        ) : (
                            <span />
                        )}
                        {posts.next && (
                            <Button asChild variant="secondary">
                                <Link href={posts.next} rel="next">
                                    {t('Older posts')}
                                    <ArrowRight aria-hidden="true" />
                                </Link>
                            </Button>
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}
