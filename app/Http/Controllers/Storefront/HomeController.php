<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Presenters\ContentPresenter;
use App\Http\Presenters\ProductPresenter;
use App\Models\Category;
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
        $home = settings()->group('home');
        $story = settings()->group('story');

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
                'name' => $c->name,
                'slug' => $c->slug,
                'tagline' => $c->tagline,
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
            title: (string) settings()->get('seo.title'),
            description: (string) settings()->get('seo.description'),
            canonical: url('/'),
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
            'faqs' => Faq::query()->published()->where('show_on_home', true)->limit(4)->get(['id', 'question', 'answer']),
            'newsletterEnabled' => (bool) $home['newsletter_enabled'],
        ], $seo);
    }
}
