<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Presenters\ContentPresenter;
use App\Http\Presenters\ProductPresenter;
use App\Models\Faq;
use App\Models\Page;
use App\Services\Seo\Seo;
use App\Services\Seo\StructuredData;
use Inertia\Response;

/**
 * Founder story, FAQ and editable policy pages.
 */
class ContentController extends Controller
{
    public function story(): Response
    {
        $story = settings()->localized('story');

        $seo = Seo::page(
            __('Our story'),
            __('How the founder of Aloe & Soul went from London to Marrakech, and why wellbeing and personal growth are at the heart of the brand.'),
        );
        $seo->image = ContentPresenter::storyImage($story)['src'];
        $seo->jsonLd = [StructuredData::breadcrumbs([
            ['name' => __('Home'), 'url' => localized_route('home')],
            ['name' => __('Our story'), 'url' => localized_route('story')],
        ])];

        return $this->render('story', [
            'story' => [
                'heading' => $story['heading'],
                'intro' => $story['intro'],
                'image' => ContentPresenter::storyImage($story),
                'milestones' => $story['milestones'],
                'chapters' => array_map(fn (array $c) => [
                    'key' => $c['key'],
                    'title' => $c['title'],
                    'paragraphs' => array_values(array_filter(array_map('trim', explode("\n\n", $c['body'])))),
                ], $story['chapters']),
                'closing' => $story['closing'],
                'disclaimer' => $story['disclaimer'],
                'tagline' => settings()->localizedGet('store.tagline'),
            ],
        ], $seo);
    }

    public function faq(): Response
    {
        return $this->render('faq', [
            'faqs' => Faq::query()->published()->get()
                ->map(fn (Faq $f) => ['id' => $f->id, 'question' => $f->translated('question'), 'answer' => $f->translated('answer')]),
        ], Seo::page(__('Frequently asked questions'), __('Answers about ordering, cash on delivery, delivery areas and your order at Aloe & Soul.')));
    }

    public function page(string $slug): Response
    {
        $page = Page::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return $this->render('page', [
            'page' => [
                'title' => $page->translated('title'),
                'slug' => $page->slug,
                'body_html' => ProductPresenter::markdown((string) $page->translated('body')),
                'updated_at' => $page->updated_at?->toDateString(),
            ],
        ], Seo::page((string) ($page->translated('seo_title') ?: $page->translated('title')), $page->translated('seo_description')));
    }
}
