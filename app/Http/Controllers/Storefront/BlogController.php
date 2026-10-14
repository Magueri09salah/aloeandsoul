<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Presenters\PostPresenter;
use App\Models\BlogPost;
use App\Services\Seo\Seo;
use App\Services\Seo\StructuredData;
use Illuminate\Http\Request;
use Inertia\Response;

class BlogController extends Controller
{
    public const PER_PAGE = 9;

    public function index(Request $request): Response
    {
        $blog = settings()->localized('blog');
        $posts = BlogPost::query()->live()->latest('published_at')->latest('id')->paginate(self::PER_PAGE);
        $page = $posts->currentPage();

        $seo = new Seo(
            title: $page > 1 ? __(':title — page :page', ['title' => $blog['heading'], 'page' => $page]) : (string) $blog['heading'],
            description: (string) ($blog['intro'] ?: settings()->localizedGet('seo.description')),
            canonical: $page > 1 ? localized_route('blog').'?page='.$page : localized_route('blog'),
            jsonLd: [StructuredData::breadcrumbs([
                ['name' => __('Home'), 'url' => localized_route('home')],
                ['name' => (string) $blog['heading'], 'url' => localized_route('blog')],
            ])],
        );

        return $this->render('blog/index', [
            'heading' => $blog['heading'],
            'intro' => $blog['intro'],
            'posts' => [
                'data' => collect($posts->items())->map(fn (BlogPost $p) => PostPresenter::card($p)),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'prev' => $posts->previousPageUrl(),
                'next' => $posts->nextPageUrl(),
            ],
        ], $seo);
    }

    public function show(BlogPost $post): Response
    {
        // Drafts and scheduled posts do not exist publicly yet.
        abort_unless($post->isLive(), 404);

        $detail = PostPresenter::detail($post);
        $blog = settings()->localized('blog');

        $seo = new Seo(
            title: (string) ($post->translated('seo_title') ?: $post->translated('title')),
            description: (string) ($post->translated('seo_description') ?: $detail['excerpt']),
            canonical: localized_route('blog.show', $post),
            image: $detail['cover']['src'] ?? null,
            type: 'article',
            jsonLd: [
                StructuredData::blogPosting($post, $detail['cover']['src'] ?? null),
                StructuredData::breadcrumbs([
                    ['name' => __('Home'), 'url' => localized_route('home')],
                    ['name' => (string) $blog['heading'], 'url' => localized_route('blog')],
                    ['name' => (string) $post->translated('title'), 'url' => localized_route('blog.show', $post)],
                ]),
            ],
        );

        $more = BlogPost::query()->live()->whereKeyNot($post->id)->latest('published_at')->limit(3)->get();

        return $this->render('blog/show', [
            'post' => $detail,
            'blogHeading' => $blog['heading'],
            'more' => $more->map(fn (BlogPost $p) => PostPresenter::card($p)),
        ], $seo);
    }
}
