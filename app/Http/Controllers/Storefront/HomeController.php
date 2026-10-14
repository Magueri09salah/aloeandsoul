<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Presenters\ContentPresenter;
use App\Http\Presenters\PostPresenter;
use App\Http\Presenters\ProductPresenter;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Collaborator;
use App\Models\Faq;
use App\Models\Product;
use App\Services\Catalog\ProductQuery;
use App\Services\Seo\Seo;
use App\Services\Seo\StructuredData;
use Illuminate\Support\Str;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(ProductQuery $products): Response
    {
        $home = settings()->localized('home');
        $story = settings()->localized('story');

        $featured = $products->published()
            ->where('is_featured', true)
            ->orderBy('sort_order')->orderByDesc('published_at')
            ->limit(8)->get();

        if ($featured->isEmpty()) {
            $featured = $products->published()->orderByDesc('published_at')->limit(4)->get();
        }
        // Fill complete rows of four on desktop.
        if ($featured->count() > 4) {
            $featured = $featured->take(4 * intdiv($featured->count(), 4));
        }

        $bestsellers = $products->published()
            ->where('is_bestseller', true)
            ->whereNotIn('products.id', $featured->modelKeys())
            ->orderBy('sort_order')
            ->limit(4)->get();

        $categories = Category::query()->active()
            ->withCount(['publishedProducts as product_count'])
            ->get()
            ->map(fn (Category $c) => [
                'name' => $c->translated('name'),
                'slug' => $c->slug,
                'tagline' => $c->translated('tagline'),
                'tint' => $c->tint,
                'motif' => $c->motif,
                'product_count' => $c->product_count,
            ]);

        $ritual = null;
        if ($home['ritual_enabled'] && ! empty($home['ritual_steps'])) {
            $steps = collect(is_array($home['ritual_steps']) ? $home['ritual_steps'] : []);
            $ritualProducts = $products->published()->whereIn('products.id', $steps->pluck('product_id'))->get()->keyBy('id');
            $ritual = [
                'heading' => $home['ritual_heading'],
                'intro' => $home['ritual_intro'],
                'steps' => $steps
                    ->filter(fn ($s) => $ritualProducts->has($s['product_id'] ?? 0))
                    ->map(fn ($s) => ['note' => $s['note'] ?? '', 'product' => ProductPresenter::card($ritualProducts[$s['product_id']])])
                    ->values(),
            ];
            if ($ritual['steps']->isEmpty()) {
                $ritual = null;
            }
        }

        $seo = new Seo(
            title: (string) settings()->localizedGet('seo.title'),
            description: (string) settings()->localizedGet('seo.description'),
            canonical: localized_route('home'),
            image: ContentPresenter::heroImage($home)['src'],
            jsonLd: [StructuredData::organization(), StructuredData::website()],
        );

        return $this->render('home', [
            'hero' => [
                'heading' => $home['hero_heading'],
                'text' => $home['hero_text'],
                'attribution' => $home['hero_attribution'],
                'image' => ContentPresenter::heroImage($home),
            ],
            'intro' => ['heading' => $home['intro_heading'], 'text' => $home['intro_text']],
            'featuredHeading' => $home['featured_heading'],
            'featured' => $featured->map(fn (Product $p) => ProductPresenter::card($p)),
            'bestsellers' => $bestsellers->map(fn (Product $p) => ProductPresenter::card($p)),
            'categories' => $categories,
            'ritual' => $ritual,
            'journeys' => collect(is_array($story['chapters']) ? $story['chapters'] : [])->map(fn (array $c) => [
                'key' => $c['key'],
                'title' => $c['title'],
                'excerpt' => Str::before($c['body'], "\n"),
            ]),
            'storyImage' => ContentPresenter::storyImage($story),
            'faqs' => Faq::query()->published()->where('show_on_home', true)->limit(4)->get()
                ->map(fn (Faq $f) => ['id' => $f->id, 'question' => $f->translated('question'), 'answer' => $f->translated('answer')]),
            'newsletterEnabled' => (bool) $home['newsletter_enabled'],
            'collaborators' => $home['collaborators_enabled'] ? [
                'heading' => $home['collaborators_heading'],
                'intro' => $home['collaborators_intro'],
                'items' => Collaborator::query()->visible()->get()->map(fn (Collaborator $c) => PostPresenter::collaborator($c)),
            ] : null,
            'latestPosts' => $home['blog_enabled'] ? [
                'heading' => $home['blog_heading'],
                'items' => BlogPost::query()->live()->latest('published_at')->limit(3)->get()->map(fn (BlogPost $p) => PostPresenter::card($p)),
            ] : null,
        ], $seo);
    }
}
